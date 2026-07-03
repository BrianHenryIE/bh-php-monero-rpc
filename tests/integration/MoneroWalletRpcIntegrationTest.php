<?php

/**
 * Read-only integration tests for the Wallet RPC client against the seeded
 * deterministic regtest chain. Nothing in this class may mutate chain or
 * wallet state (proof generation and signing mutate nothing).
 *
 * Several Wallet methods still return stdClass; where a typed model is added
 * later these assertions should move to the typed getters.
 * NB: RpcClient maps response keys to camelCase, including on stdClass.
 *
 * @package brianhenryie/bh-php-monero-rpc
 */

namespace BrianHenryIE\MoneroRpc;

use BrianHenryIE\MoneroRpc\Exception\MoneroRpcErrorException;
use BrianHenryIE\MoneroRpc\Wallet\AddressValidation;
use BrianHenryIE\MoneroRpc\Wallet\KeyImagesExport;
use BrianHenryIE\MoneroRpc\Wallet\MakeUriResult;
use BrianHenryIE\MoneroRpc\Wallet\ParseUriResult;
use BrianHenryIE\MoneroRpc\Wallet\TransferType;
use BrianHenryIE\MoneroRpc\Wallet\WalletKeyType;

/**
 * @coversDefaultClass \BrianHenryIE\MoneroRpc\Wallet
 */
class MoneroWalletRpcIntegrationTest extends MoneroRpcIntegrationTestCase
{
    public function testGetVersion(): void
    {
        $result = self::$minerWalletRpcClient->getVersion();

        self::assertGreaterThan(0, $result->version);
    }

    public function testRecipientWalletBalanceIsExactlyTheSeedTransfer(): void
    {
        $recipientWallet = $this->openRecipientWallet();
        $recipientWallet->refresh();

        $result = $recipientWallet->getBalance();

        self::assertSame(
            MoneroRegtestFixture::EXPECTED_RECIPIENT_BALANCE_ATOMIC_UNITS,
            $result->balance->toAtomicUnitsString()
        );
    }

    public function testMinerWalletBalanceMatchesManifest(): void
    {
        $minerWallet = $this->openMinerWallet();
        $minerWallet->refresh();

        $result = $minerWallet->getBalance();

        self::assertSame(self::$manifest['miner_wallet_balance_atomic_units'], $result->balance->toAtomicUnitsString());
    }

    public function testGetAddressIsMnemonicDerivedConstant(): void
    {
        $minerResult = $this->openMinerWallet()->getAddress();
        $recipientResult = $this->openRecipientWallet()->getAddress();

        self::assertSame(MoneroRegtestFixture::MINER_WALLET_PRIMARY_ADDRESS, $minerResult->address);
        self::assertSame(MoneroRegtestFixture::RECIPIENT_WALLET_PRIMARY_ADDRESS, $recipientResult->address);
    }

    public function testGetAddressIndex(): void
    {
        $minerWallet = $this->openMinerWallet();

        $result = $minerWallet->getAddressIndex(MoneroRegtestFixture::MINER_WALLET_PRIMARY_ADDRESS);

        self::assertSame(0, $result->index->major);
        self::assertSame(0, $result->index->minor);
    }

    public function testWalletHeightMatchesChainHeight(): void
    {
        $minerWallet = $this->openMinerWallet();
        $minerWallet->refresh();

        $result = $minerWallet->getHeight();

        self::assertSame(MoneroRegtestFixture::EXPECTED_CHAIN_HEIGHT_AFTER_SEED, $result);
    }

    public function testQueryKeyMnemonicRoundTripsTheCommittedSeed(): void
    {
        $minerWallet = $this->openMinerWallet();

        $result = $minerWallet->queryKey(WalletKeyType::Mnemonic);

        self::assertSame(MoneroRegtestFixture::MINER_WALLET_MNEMONIC, $result->key);
    }

    public function testGetTransfersRecipientSeesExactlyOneIncomingTransfer(): void
    {
        $recipientWallet = $this->openRecipientWallet();
        $recipientWallet->refresh();

        $result = $recipientWallet->getTransfers([TransferType::In]);

        self::assertCount(1, $result->in);
        self::assertSame(self::$manifest['transfer_txid'], $result->in[0]->txid);
        self::assertSame(MoneroRegtestFixture::TRANSFER_AMOUNT_ATOMIC_UNITS, (string) $result->in[0]->amount);
        self::assertSame(self::$manifest['transfer_block_height'], (int) $result->in[0]->height);
    }

    public function testGetTransferByTxid(): void
    {
        $minerWallet = $this->openMinerWallet();
        $minerWallet->refresh();

        $result = $minerWallet->getTransferByTxid(self::$manifest['transfer_txid']);

        self::assertSame(TransferType::Out, $result->transfer->type);
        self::assertSame(MoneroRegtestFixture::TRANSFER_AMOUNT_ATOMIC_UNITS, (string) $result->transfer->amount);
        self::assertSame(self::$manifest['transfer_fee_atomic_units'], (string) $result->transfer->fee);
    }

    public function testIncomingTransfers(): void
    {
        $recipientWallet = $this->openRecipientWallet();
        $recipientWallet->refresh();

        $result = $recipientWallet->incomingTransfers();

        self::assertCount(1, $result->transfers);
        self::assertSame(MoneroRegtestFixture::TRANSFER_AMOUNT_ATOMIC_UNITS, (string) $result->transfers[0]->amount);
    }

    public function testGetTxKeyMatchesManifest(): void
    {
        $minerWallet = $this->openMinerWallet();

        $result = $minerWallet->getTxKey(self::$manifest['transfer_txid']);

        self::assertSame(self::$manifest['transfer_tx_key'], $result->txKey);
    }

    /**
     * Proves, via the daemon, that the seed transfer paid the recipient.
     */
    public function testCheckTxKey(): void
    {
        $minerWallet = $this->openMinerWallet();

        $result = $minerWallet->checkTxKey(
            MoneroRegtestFixture::RECIPIENT_WALLET_PRIMARY_ADDRESS,
            self::$manifest['transfer_txid'],
            self::$manifest['transfer_tx_key']
        );

        self::assertSame(MoneroRegtestFixture::TRANSFER_AMOUNT_ATOMIC_UNITS, (string) $result->received);
        self::assertFalse($result->inPool);
    }

    public function testGetAndCheckTxProof(): void
    {
        $minerWallet = $this->openMinerWallet();

        $proof = $minerWallet->getTxProof(
            MoneroRegtestFixture::RECIPIENT_WALLET_PRIMARY_ADDRESS,
            self::$manifest['transfer_txid']
        );

        $result = $minerWallet->checkTxProof(
            MoneroRegtestFixture::RECIPIENT_WALLET_PRIMARY_ADDRESS,
            self::$manifest['transfer_txid'],
            $proof->signature
        );

        self::assertTrue($result->good);
        self::assertSame(MoneroRegtestFixture::TRANSFER_AMOUNT_ATOMIC_UNITS, (string) $result->received);
    }

    public function testGetAndCheckSpendProof(): void
    {
        $minerWallet = $this->openMinerWallet();

        $proof = $minerWallet->getSpendProof(self::$manifest['transfer_txid']);

        $result = $minerWallet->checkSpendProof(
            self::$manifest['transfer_txid'],
            $proof->signature
        );

        self::assertTrue($result->good);
    }

    public function testGetAndCheckReserveProof(): void
    {
        $minerWallet = $this->openMinerWallet();
        $minerWallet->refresh();

        $proof = $minerWallet->getReserveProof();

        $result = $minerWallet->checkReserveProof(
            MoneroRegtestFixture::MINER_WALLET_PRIMARY_ADDRESS,
            $proof->signature
        );

        self::assertTrue($result->good);
        self::assertFalse($result->total->isZero());
    }

    public function testMakeAndSplitIntegratedAddressRoundTrip(): void
    {
        $minerWallet = $this->openMinerWallet();

        $integratedAddressResult = $minerWallet->makeIntegratedAddress();

        $splitResult = $minerWallet->splitIntegratedAddress(
            $integratedAddressResult->integratedAddress
        );

        self::assertSame(MoneroRegtestFixture::MINER_WALLET_PRIMARY_ADDRESS, $splitResult->standardAddress);
        self::assertSame($integratedAddressResult->paymentId, $splitResult->paymentId);
    }

    public function testSignAndVerifyRoundTrip(): void
    {
        $minerWallet = $this->openMinerWallet();
        $signedData = 'integration test data';

        $signResult = $minerWallet->sign($signedData);

        $verifyResult = $minerWallet->verify(
            $signedData,
            MoneroRegtestFixture::MINER_WALLET_PRIMARY_ADDRESS,
            $signResult->signature
        );

        self::assertTrue($verifyResult->good);
    }

    public function testVerifyRejectsTamperedData(): void
    {
        $minerWallet = $this->openMinerWallet();

        $signResult = $minerWallet->sign('integration test data');

        $verifyResult = $minerWallet->verify(
            'tampered data',
            MoneroRegtestFixture::MINER_WALLET_PRIMARY_ADDRESS,
            $signResult->signature
        );

        self::assertFalse($verifyResult->good);
    }

    public function testGetLanguages(): void
    {
        $result = self::$minerWalletRpcClient->getLanguages();

        self::assertContains('English', $result->languages);
    }

    public function testGetAccounts(): void
    {
        $minerWallet = $this->openMinerWallet();
        $minerWallet->refresh();

        $result = $minerWallet->getAccounts();

        self::assertCount(1, $result->subaddressAccounts);
        self::assertSame(
            self::$manifest['miner_wallet_balance_atomic_units'],
            (string) $result->totalBalance
        );
    }

    public function testExportOutputs(): void
    {
        $minerWallet = $this->openMinerWallet();
        $minerWallet->refresh();

        $result = $minerWallet->exportOutputs();

        self::assertNotEmpty($result->outputsDataHex);
    }

    public function testExportKeyImages(): void
    {
        $minerWallet = $this->openMinerWallet();
        $minerWallet->refresh();

        $result = $minerWallet->exportKeyImages(true);

        self::assertInstanceOf(KeyImagesExport::class, $result);
        self::assertNotEmpty($result->signedKeyImages);
        self::assertNotEmpty($result->signedKeyImages[0]->keyImage);
    }

    /**
     * ERROR CATEGORY (wallet-rpc business error): opening a non-existent wallet surfaces the
     * wallet-rpc's JSON-RPC error as a MoneroRpcErrorException.
     */
    public function testOpenWalletFailureSurfacedAsTypedException(): void
    {
        try {
            self::$minerWalletRpcClient->openWallet('this_wallet_does_not_exist', 'wrong');
            self::fail('Expected MoneroRpcErrorException');
        } catch (MoneroRpcErrorException $e) {
            self::assertStringContainsString('Failed to open wallet', $e->getMessage());
        } finally {
            self::forgetOpenWalletState();
        }
    }

    public function testValidateAddress(): void
    {
        $miner = $this->openMinerWallet();

        $valid = $miner->validateAddress(MoneroRegtestFixture::MINER_WALLET_PRIMARY_ADDRESS);
        self::assertInstanceOf(AddressValidation::class, $valid);
        self::assertTrue($valid->valid);
        self::assertFalse($valid->integrated);
        self::assertFalse($valid->subaddress);

        // A corrupted-checksum address is invalid.
        $corrupted = substr(MoneroRegtestFixture::MINER_WALLET_PRIMARY_ADDRESS, 0, -1) . 'X';
        self::assertFalse($miner->validateAddress($corrupted)->valid);

        // An integrated address is flagged as such.
        $integratedAddress = $miner->makeIntegratedAddress()->integratedAddress;
        $integrated = $miner->validateAddress($integratedAddress);
        self::assertTrue($integrated->valid);
        self::assertTrue($integrated->integrated);
    }

    public function testMakeAndParseUriRoundTrip(): void
    {
        $miner = $this->openMinerWallet();

        $uri = $miner->makeUri(
            MoneroRegtestFixture::MINER_WALLET_PRIMARY_ADDRESS,
            MoneroAmount::fromXmr('1.23'),
            recipientName: 'Alice',
            txDescription: 'lunch'
        );
        self::assertInstanceOf(MakeUriResult::class, $uri);
        self::assertStringStartsWith('monero:', $uri->uri);

        $parsed = $miner->parseUri($uri->uri);
        self::assertInstanceOf(ParseUriResult::class, $parsed);
        self::assertSame(MoneroRegtestFixture::MINER_WALLET_PRIMARY_ADDRESS, $parsed->uri->address);
        self::assertSame('1230000000000', $parsed->uri->amount->toAtomicUnitsString());
        self::assertSame('Alice', $parsed->uri->recipientName);
        self::assertSame('lunch', $parsed->uri->txDescription);
    }
}
