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

/**
 * @coversDefaultClass \BrianHenryIE\MoneroRpc\Wallet
 */
class MoneroWalletRpcIntegrationTest extends MoneroRpcIntegrationTestCase
{
    public function testGetVersion(): void
    {
        $result = self::$minerWalletRpcClient->getVersion();

        self::assertGreaterThan(0, $result->getVersion());
    }

    public function testRecipientWalletBalanceIsExactlyTheSeedTransfer(): void
    {
        $recipientWallet = $this->openRecipientWallet();
        $recipientWallet->refresh();

        $result = $recipientWallet->getBalance();

        self::assertSame(MoneroRegtestFixture::EXPECTED_RECIPIENT_BALANCE_ATOMIC_UNITS, $result->getBalance());
    }

    public function testMinerWalletBalanceMatchesManifest(): void
    {
        $minerWallet = $this->openMinerWallet();
        $minerWallet->refresh();

        $result = $minerWallet->getBalance();

        self::assertSame(self::$manifest['miner_wallet_balance_atomic_units'], $result->getBalance());
    }

    public function testGetAddressIsMnemonicDerivedConstant(): void
    {
        $minerResult = $this->openMinerWallet()->getAddress();
        $recipientResult = $this->openRecipientWallet()->getAddress();

        self::assertSame(MoneroRegtestFixture::MINER_WALLET_PRIMARY_ADDRESS, $minerResult->getAddress());
        self::assertSame(MoneroRegtestFixture::RECIPIENT_WALLET_PRIMARY_ADDRESS, $recipientResult->getAddress());
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

        $result = $minerWallet->queryKey('mnemonic');

        self::assertSame(MoneroRegtestFixture::MINER_WALLET_MNEMONIC, $result->getKey());
    }

    public function testGetTransfersRecipientSeesExactlyOneIncomingTransfer(): void
    {
        $recipientWallet = $this->openRecipientWallet();
        $recipientWallet->refresh();

        $result = $recipientWallet->getTransfers(['in']);

        self::assertCount(1, $result->in);
        self::assertSame(self::$manifest['transfer_txid'], $result->in[0]->txid);
        self::assertSame(MoneroRegtestFixture::TRANSFER_AMOUNT_ATOMIC_UNITS, (int) $result->in[0]->amount);
        self::assertSame(self::$manifest['transfer_block_height'], (int) $result->in[0]->height);
    }

    public function testGetTransferByTxid(): void
    {
        $minerWallet = $this->openMinerWallet();
        $minerWallet->refresh();

        $result = $minerWallet->getTransferByTxid(self::$manifest['transfer_txid']);

        self::assertSame('out', $result->transfer->type);
        self::assertSame(MoneroRegtestFixture::TRANSFER_AMOUNT_ATOMIC_UNITS, (int) $result->transfer->amount);
        self::assertSame(self::$manifest['transfer_fee_atomic_units'], (int) $result->transfer->fee);
    }

    public function testIncomingTransfers(): void
    {
        $recipientWallet = $this->openRecipientWallet();
        $recipientWallet->refresh();

        $result = $recipientWallet->incomingTransfers();

        self::assertCount(1, $result->transfers);
        self::assertSame(MoneroRegtestFixture::TRANSFER_AMOUNT_ATOMIC_UNITS, (int) $result->transfers[0]->amount);
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

        self::assertSame(MoneroRegtestFixture::TRANSFER_AMOUNT_ATOMIC_UNITS, (int) $result->received);
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
        self::assertSame(MoneroRegtestFixture::TRANSFER_AMOUNT_ATOMIC_UNITS, (int) $result->received);
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
        self::assertGreaterThan(0, (int) $result->total);
    }

    public function testMakeAndSplitIntegratedAddressRoundTrip(): void
    {
        $minerWallet = $this->openMinerWallet();

        $integratedAddressResult = $minerWallet->makeIntegratedAddress();

        $splitResult = $minerWallet->splitIntegratedAddress(
            $integratedAddressResult->getIntegratedAddress()
        );

        self::assertSame(MoneroRegtestFixture::MINER_WALLET_PRIMARY_ADDRESS, $splitResult->standardAddress);
        self::assertSame($integratedAddressResult->getPaymentId(), $splitResult->paymentId);
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
            (int) $result->totalBalance
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

        self::assertIsObject($result);
        self::assertNotEmpty($result->signedKeyImages);
    }
}
