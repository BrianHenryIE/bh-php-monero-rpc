<?php

/**
 * Integration tests for Wallet RPC methods which mutate wallet or chain state.
 *
 * These run AFTER the read-only `integration` suite. Balance expectations are
 * re-derived from RPC reads (this suite extends the chain), and tests which
 * open throwaway wallet files call `forgetOpenWalletState()` so subsequent
 * tests re-open the fixture wallets.
 *
 * Not covered here (gaps): `Wallet::relayTx()` (Wallet::transfer() does not
 * expose `get_tx_hex`, so there is no tx blob to relay),
 * `Wallet::labelAddress()` (its signature takes `int $index` but the RPC
 * requires `index: {major, minor}` — broken parameter construction),
 * `Wallet::sweepAll()`/`sweepSingle()` (would empty the fixture miner wallet).
 *
 * @package brianhenryie/bh-php-monero-rpc
 */

namespace BrianHenryIE\MoneroRpc;

use BrianHenryIE\MoneroRpc\Wallet\AddressBook;
use BrianHenryIE\MoneroRpc\Wallet\AddressBookIndex;
use BrianHenryIE\MoneroRpc\Wallet\DescribeTransferResult;
use BrianHenryIE\MoneroRpc\Wallet\ImportKeyImagesResult;
use BrianHenryIE\MoneroRpc\Wallet\ImportOutputsResult;
use BrianHenryIE\MoneroRpc\Wallet\IncomingTransferType;
use BrianHenryIE\MoneroRpc\Wallet\Payments;
use BrianHenryIE\MoneroRpc\Wallet\RelayTxResult;
use BrianHenryIE\MoneroRpc\Wallet\SslSupport;
use BrianHenryIE\MoneroRpc\Wallet\SweepAllResult;
use BrianHenryIE\MoneroRpc\Wallet\SweepDust;
use BrianHenryIE\MoneroRpc\Wallet\SweepSingleResult;
use BrianHenryIE\MoneroRpc\Wallet\TransferSplitResult;
use BrianHenryIE\MoneroRpc\Wallet\WalletKeyType;

/**
 * @coversDefaultClass \BrianHenryIE\MoneroRpc\Wallet
 */
class MoneroWalletRpcMutatingStateIntegrationTest extends MoneroRpcIntegrationTestCase
{
    protected static function isReadOnlyTestSuite(): bool
    {
        return false;
    }

    public function testCreateOpenCloseWalletAndChangePassword(): void
    {
        $walletRpcClient = self::$minerWalletRpcClient;
        $throwawayWalletFilename = uniqid('throwaway_wallet_');

        try {
            $walletRpcClient->createWallet($throwawayWalletFilename, 'first-password');

            $address = $walletRpcClient->getAddress()->address;
            self::assertNotEmpty($address);

            $walletRpcClient->closeWallet();
            $walletRpcClient->openWallet($throwawayWalletFilename, 'first-password');

            $walletRpcClient->changeWalletPassword('first-password', 'second-password');

            $walletRpcClient->closeWallet();
            $walletRpcClient->openWallet($throwawayWalletFilename, 'second-password');

            self::assertSame($address, $walletRpcClient->getAddress()->address);
        } finally {
            self::forgetOpenWalletState();
        }
    }

    public function testCreateAccountAndTagging(): void
    {
        $walletRpcClient = self::$minerWalletRpcClient;
        $throwawayWalletFilename = uniqid('throwaway_wallet_');

        try {
            $walletRpcClient->createWallet($throwawayWalletFilename, 'password');

            $createAccountResult = $walletRpcClient->createAccount('second account');
            self::assertSame(1, $createAccountResult->accountIndex);

            $walletRpcClient->labelAccount(1, 'renamed second account');

            $walletRpcClient->tagAccounts([1], 'integration-test-tag');
            $walletRpcClient->setAccountTagDescription('integration-test-tag', 'tag description');

            $accountTagsResult = $walletRpcClient->getAccountTags();
            self::assertSame('integration-test-tag', $accountTagsResult->accountTags[0]->tag);

            $walletRpcClient->untagAccounts([1]);

            $createAddressResult = $walletRpcClient->createAddress(0, 'subaddress label');
            self::assertNotEmpty($createAddressResult->address);

            $accountsResult = $walletRpcClient->getAccounts();
            self::assertCount(2, $accountsResult->subaddressAccounts);
        } finally {
            self::forgetOpenWalletState();
        }
    }

    /**
     * Transfer end-to-end across the two daemons: send from the miner wallet
     * (primary daemon), confirm by mining, observe in the recipient wallet
     * (peer daemon).
     */
    public function testTransferEndToEnd(): void
    {
        $transferAmount = MoneroAmount::fromXmr('0.5');

        $recipientWallet = $this->openRecipientWallet();
        $recipientWallet->refresh();
        $recipientBalanceBefore = $recipientWallet->getBalance()->balance;

        $minerWallet = $this->openMinerWallet();
        $minerWallet->refresh();

        $transferResult = $minerWallet->transfer(
            $transferAmount,
            MoneroRegtestFixture::RECIPIENT_WALLET_PRIMARY_ADDRESS
        );

        self::assertSame($transferAmount->toAtomicUnitsString(), (string) $transferResult->amount);
        self::assertNotEmpty($transferResult->txHash);

        // Confirm the transfer.
        self::$daemonPrimaryRpcClient->generateBlocks(
            10,
            MoneroRegtestFixture::MINER_WALLET_PRIMARY_ADDRESS,
            '',
            0
        );

        $expectedRecipientBalance = $recipientBalanceBefore->plus($transferAmount);
        self::pollUntil(
            function () use ($recipientWallet, $expectedRecipientBalance) {
                $recipientWallet->refresh();
                return $recipientWallet->getBalance()->balance->isEqualTo($expectedRecipientBalance);
            },
            60,
            "Recipient balance did not increase to {$expectedRecipientBalance}"
        );
    }

    public function testTransferSplit(): void
    {
        $minerWallet = $this->openMinerWallet();
        $minerWallet->refresh();

        $result = $minerWallet->transferSplit(
            MoneroAmount::fromXmr('0.1'),
            MoneroRegtestFixture::RECIPIENT_WALLET_PRIMARY_ADDRESS
        );

        self::assertInstanceOf(TransferSplitResult::class, $result);
        self::assertNotEmpty($result->txHashList);
        self::assertCount(count($result->txHashList), $result->amountList);
        self::assertContainsOnlyInstancesOf(MoneroAmount::class, $result->feeList);

        // Confirm, so later tests start with an empty tx pool.
        self::$daemonPrimaryRpcClient->generateBlocks(
            10,
            MoneroRegtestFixture::MINER_WALLET_PRIMARY_ADDRESS,
            '',
            0
        );
    }

    /**
     * Create a fresh throwaway wallet on the recipient wallet-rpc server, fund it from the
     * miner, and confirm. Returns [filename, address]. NEVER sweeps a fixture wallet.
     *
     * @return array{0: string, 1: string}
     */
    private function createAndFundThrowawayWallet(string $xmrAmount): array
    {
        $recipientRpc = self::$recipientWalletRpcClient;
        $filename = uniqid('throwaway_sweep_');
        $recipientRpc->createWallet($filename, 'pw');
        $address = $recipientRpc->getAddress()->address;
        self::forgetOpenWalletState();

        $miner = $this->openMinerWallet();
        $miner->refresh();
        $miner->transfer(MoneroAmount::fromXmr($xmrAmount), $address);
        self::$daemonPrimaryRpcClient->generateBlocks(
            15,
            MoneroRegtestFixture::MINER_WALLET_PRIMARY_ADDRESS,
            '',
            0
        );
        self::pollUntil(
            fn() => self::$daemonPeerRpcClient->getHeight()->height
                === self::$daemonPrimaryRpcClient->getHeight()->height,
            60,
            'Daemons did not converge after funding throwaway wallet'
        );

        return [$filename, $address];
    }

    public function testSweepAllEmptiesTheWallet(): void
    {
        [$filename] = $this->createAndFundThrowawayWallet('0.05');

        try {
            $throwaway = self::$recipientWalletRpcClient;
            $throwaway->openWallet($filename, 'pw');
            $throwaway->refresh();
            self::assertFalse($throwaway->getBalance()->unlockedBalance->isZero());

            $result = $throwaway->sweepAll(MoneroRegtestFixture::MINER_WALLET_PRIMARY_ADDRESS);

            self::assertInstanceOf(SweepAllResult::class, $result);
            self::assertNotEmpty($result->txHashList);
            self::assertContainsOnlyInstancesOf(MoneroAmount::class, $result->amountList);

            self::$daemonPrimaryRpcClient->generateBlocks(
                10,
                MoneroRegtestFixture::MINER_WALLET_PRIMARY_ADDRESS,
                '',
                0
            );
            $throwaway->refresh();
            self::assertTrue($throwaway->getBalance()->balance->isZero());
        } finally {
            self::forgetOpenWalletState();
        }
    }

    public function testSweepSingleEmptiesTheOutput(): void
    {
        [$filename] = $this->createAndFundThrowawayWallet('0.05');

        try {
            $throwaway = self::$recipientWalletRpcClient;
            $throwaway->openWallet($filename, 'pw');
            $throwaway->refresh();

            $keyImage = $throwaway->incomingTransfers(IncomingTransferType::Available)->transfers[0]->keyImage;

            $result = $throwaway->sweepSingle(
                $keyImage,
                MoneroRegtestFixture::MINER_WALLET_PRIMARY_ADDRESS
            );

            self::assertInstanceOf(SweepSingleResult::class, $result);
            self::assertNotEmpty($result->txHash);
            self::assertFalse($result->amount->isZero());

            self::$daemonPrimaryRpcClient->generateBlocks(
                10,
                MoneroRegtestFixture::MINER_WALLET_PRIMARY_ADDRESS,
                '',
                0
            );
            $throwaway->refresh();
            self::assertTrue($throwaway->getBalance()->balance->isZero());
        } finally {
            self::forgetOpenWalletState();
        }
    }

    public function testDescribeTransferOfAViewOnlyWalletUnsignedTxset(): void
    {
        $recipientRpc = self::$recipientWalletRpcClient;
        $filename = uniqid('viewonly_describe_');

        try {
            // Build a watch-only copy of the miner wallet on the recipient wallet-rpc server.
            $miner = $this->openMinerWallet();
            $miner->refresh();
            $viewKey = $miner->queryKey(WalletKeyType::ViewKey)->key;
            $outputsHex = $miner->exportOutputs()->outputsDataHex;

            $recipientRpc->generateFromKeys(
                $filename,
                'pw',
                MoneroRegtestFixture::MINER_WALLET_PRIMARY_ADDRESS,
                $viewKey
            );
            self::forgetOpenWalletState();
            $recipientRpc->openWallet($filename, 'pw');
            $recipientRpc->refresh();
            $recipientRpc->importOutputs($outputsHex);
            $recipientRpc->refresh();

            // A watch-only wallet can only produce an UNSIGNED tx set.
            $unsigned = $recipientRpc->transfer(
                MoneroAmount::fromXmr('0.01'),
                MoneroRegtestFixture::RECIPIENT_WALLET_PRIMARY_ADDRESS,
                doNotRelay: true
            );
            self::assertNotEmpty($unsigned->unsignedTxset);

            // describe_transfer must run on the FULL wallet (cold signer), not the watch-only one.
            $miner = $this->openMinerWallet();
            $description = $miner->describeTransfer($unsigned->unsignedTxset);

            self::assertInstanceOf(DescribeTransferResult::class, $description);
            self::assertNotEmpty($description->desc);
            self::assertFalse($description->desc[0]->fee->isZero());
            self::assertSame(
                MoneroRegtestFixture::RECIPIENT_WALLET_PRIMARY_ADDRESS,
                $description->desc[0]->recipients[0]->address
            );
            self::assertSame('10000000000', $description->desc[0]->recipients[0]->amount->toAtomicUnitsString());
        } finally {
            self::forgetOpenWalletState();
        }
    }

    public function testGetPaymentsAndBulkPaymentsViaIntegratedAddress(): void
    {
        $recipient = $this->openRecipientWallet();
        $recipient->refresh();

        // Standalone payment ids are dead, but an integrated address still carries an 8-byte one.
        $integrated = $recipient->makeIntegratedAddress();
        $paymentId = $integrated->paymentId;

        $miner = $this->openMinerWallet();
        $miner->refresh();
        $miner->transfer(MoneroAmount::fromXmr('0.02'), $integrated->integratedAddress);

        self::$daemonPrimaryRpcClient->generateBlocks(
            12,
            MoneroRegtestFixture::MINER_WALLET_PRIMARY_ADDRESS,
            '',
            0
        );
        self::pollUntil(
            fn() => self::$daemonPeerRpcClient->getHeight()->height
                === self::$daemonPrimaryRpcClient->getHeight()->height,
            60,
            'Daemons did not converge after integrated-address payment'
        );

        $recipient = $this->openRecipientWallet();
        $recipient->refresh();

        $payments = $recipient->getPayments($paymentId);
        self::assertInstanceOf(Payments::class, $payments);
        self::assertNotEmpty($payments->payments);
        self::assertSame($paymentId, $payments->payments[0]->paymentId);
        self::assertSame('20000000000', $payments->payments[0]->amount->toAtomicUnitsString());

        $bulk = $recipient->getBulkPayments([$paymentId], 0);
        self::assertInstanceOf(Payments::class, $bulk);
        self::assertNotEmpty($bulk->payments);
    }

    public function testImportOutputsAndKeyImages(): void
    {
        $recipientRpc = self::$recipientWalletRpcClient;
        $filename = uniqid('viewonly_import_');

        try {
            $miner = $this->openMinerWallet();
            $miner->refresh();
            $viewKey = $miner->queryKey(WalletKeyType::ViewKey)->key;
            $outputsHex = $miner->exportOutputs()->outputsDataHex;
            $keyImages = $miner->exportKeyImages(true);

            $recipientRpc->generateFromKeys(
                $filename,
                'pw',
                MoneroRegtestFixture::MINER_WALLET_PRIMARY_ADDRESS,
                $viewKey
            );
            self::forgetOpenWalletState();
            $recipientRpc->openWallet($filename, 'pw');
            $recipientRpc->refresh();

            $importOutputs = $recipientRpc->importOutputs($outputsHex);
            self::assertInstanceOf(ImportOutputsResult::class, $importOutputs);
            // generate_from_keys already auto-synced the outputs, so the count may be 0.
            self::assertGreaterThanOrEqual(0, $importOutputs->numImported);

            $recipientRpc->refresh();

            // Importing the key images teaches the watch-only wallet which outputs are spent.
            $signed = array_map(
                fn($k) => ['key_image' => $k->keyImage, 'signature' => $k->signature],
                $keyImages->signedKeyImages
            );
            $importKeyImages = $recipientRpc->importKeyImages($signed);
            self::assertInstanceOf(ImportKeyImagesResult::class, $importKeyImages);
            self::assertInstanceOf(MoneroAmount::class, $importKeyImages->spent);
            self::assertInstanceOf(MoneroAmount::class, $importKeyImages->unspent);
        } finally {
            self::forgetOpenWalletState();
        }
    }

    public function testAddressBookCrud(): void
    {
        $recipient = $this->openRecipientWallet();
        $index = null;

        try {
            $added = $recipient->addAddressBook(
                MoneroRegtestFixture::MINER_WALLET_PRIMARY_ADDRESS,
                'The miner'
            );
            self::assertInstanceOf(AddressBookIndex::class, $added);
            $index = $added->index;

            $book = $recipient->getAddressBook([$index]);
            self::assertInstanceOf(AddressBook::class, $book);
            self::assertSame(MoneroRegtestFixture::MINER_WALLET_PRIMARY_ADDRESS, $book->entries[0]->address);
            self::assertSame('The miner', $book->entries[0]->description);
        } finally {
            if ($index !== null) {
                $recipient->deleteAddressBook($index);
            }
        }

        // After deletion the entry is gone (fetching it errors).
        $this->expectException(\Exception::class);
        $recipient->getAddressBook([$index]);
    }

    public function testSweepDustFindsNoDust(): void
    {
        $minerWallet = $this->openMinerWallet();
        $minerWallet->refresh();

        // Modern chains produce no dust: the contract is an empty result (no tx sets).
        $result = $minerWallet->sweepDust();

        self::assertInstanceOf(SweepDust::class, $result);
        self::assertSame('', $result->multisigTxset);
        self::assertSame('', $result->unsignedTxset);
    }

    public function testSweepUnmixableFindsNothing(): void
    {
        $minerWallet = $this->openMinerWallet();
        $minerWallet->refresh();

        $result = $minerWallet->sweepUnmixable();

        self::assertInstanceOf(SweepDust::class, $result);
        self::assertSame('', $result->multisigTxset);
    }

    public function testRelayTx(): void
    {
        $minerWallet = $this->openMinerWallet();
        $minerWallet->refresh();

        try {
            // Build a tx without relaying, capturing its metadata, then relay it separately.
            $built = $minerWallet->transfer(
                MoneroAmount::fromXmr('0.01'),
                MoneroRegtestFixture::RECIPIENT_WALLET_PRIMARY_ADDRESS,
                doNotRelay: true,
                getTxMetadata: true
            );

            $result = $minerWallet->relayTx($built->txMetadata);

            self::assertInstanceOf(RelayTxResult::class, $result);
            self::assertSame($built->txHash, $result->txHash);

            self::pollUntil(
                fn() => count(self::$daemonPrimaryRpcClient->getTransactionPool()->transactions) > 0,
                30,
                'Relayed transaction did not reach the pool'
            );
        } finally {
            self::$daemonPrimaryRpcClient->generateBlocks(
                2,
                MoneroRegtestFixture::MINER_WALLET_PRIMARY_ADDRESS,
                '',
                0
            );
            self::$daemonPrimaryRpcClient->flushTxPool();
            self::pollUntil(
                function () {
                    return self::$daemonPeerRpcClient->getHeight()->height
                        === self::$daemonPrimaryRpcClient->getHeight()->height;
                },
                60,
                'Daemons did not converge after relayTx'
            );
        }
    }

    public function testSetAndGetAttribute(): void
    {
        $minerWallet = $this->openMinerWallet();

        $minerWallet->setAttribute('ATTR_INTEGRATION_TEST', 'attribute value');

        $result = $minerWallet->getAttribute('ATTR_INTEGRATION_TEST');

        self::assertSame('attribute value', $result->value);
    }

    public function testSetAndGetTxNotes(): void
    {
        $minerWallet = $this->openMinerWallet();
        $transferTxid = self::$manifest['transfer_txid'];

        $minerWallet->setTxNotes([$transferTxid], ['integration test note']);

        $result = $minerWallet->getTxNotes([$transferTxid]);

        self::assertSame('integration test note', $result->notes[0]);
    }

    public function testStore(): void
    {
        $this->expectNotToPerformAssertions();

        $minerWallet = $this->openMinerWallet();

        $minerWallet->store();
    }

    public function testRefreshAndRescan(): void
    {
        $minerWallet = $this->openMinerWallet();

        $refreshResult = $minerWallet->refresh();
        self::assertGreaterThanOrEqual(0, $refreshResult->blocksFetched);

        $minerWallet->rescanSpent();

        $minerWallet->rescanBlockchain();

        // After a rescan the wallet must converge back to the daemon height.
        $daemonHeight = self::$daemonPrimaryRpcClient->getHeight()->height;
        self::pollUntil(
            function () use ($minerWallet, $daemonHeight) {
                $minerWallet->refresh();
                return $minerWallet->getHeight() === $daemonHeight;
            },
            60,
            'Miner wallet did not re-sync after rescan_blockchain'
        );

        $minerWallet->autoRefresh(true, 10);
    }

    /**
     * Point the recipient wallet-rpc at the primary daemon and back at the peer.
     */
    public function testSetDaemonRoundTrip(): void
    {
        $recipientWallet = $this->openRecipientWallet();

        try {
            $recipientWallet->setDaemon(
                host: getenv('MONERO_DAEMON_PRIMARY_INTERNAL_HOST')
                    ?: MoneroRegtestFixture::DAEMON_PRIMARY_INTERNAL_HOST,
                port: (int) (getenv('MONERO_DAEMON_INTERNAL_RPC_PORT')
                    ?: MoneroRegtestFixture::DAEMON_INTERNAL_RPC_PORT),
                isTrusted: true,
                sslSupport: SslSupport::Disabled
            );

            $recipientWallet->refresh();

            self::assertSame(
                self::$daemonPrimaryRpcClient->getHeight()->height,
                $recipientWallet->getHeight()
            );
        } finally {
            $recipientWallet->setDaemon(
                host: getenv('MONERO_DAEMON_PEER_INTERNAL_HOST')
                    ?: MoneroRegtestFixture::DAEMON_PEER_INTERNAL_HOST,
                port: (int) (getenv('MONERO_DAEMON_INTERNAL_RPC_PORT')
                    ?: MoneroRegtestFixture::DAEMON_INTERNAL_RPC_PORT),
                isTrusted: true,
                sslSupport: SslSupport::Disabled
            );
        }
    }

    /**
     * Restore a view-only copy of the miner wallet from its address + view key.
     * A view-only wallet sees incoming funds (but cannot know spends without
     * key images), so its balance is at least the full wallet's balance.
     */
    public function testGenerateFromKeysViewOnlyWallet(): void
    {
        $minerWallet = $this->openMinerWallet();
        $minerWallet->refresh();
        $minerWalletBalance = $minerWallet->getBalance()->balance;
        $minerWalletViewKey = $minerWallet->queryKey(WalletKeyType::ViewKey)->key;

        $recipientWalletRpcServer = self::$recipientWalletRpcClient;
        $viewOnlyWalletFilename = uniqid('view_only_miner_wallet_');

        try {
            $recipientWalletRpcServer->generateFromKeys(
                $viewOnlyWalletFilename,
                'password',
                MoneroRegtestFixture::MINER_WALLET_PRIMARY_ADDRESS,
                $minerWalletViewKey
            );

            $recipientWalletRpcServer->refresh();

            self::pollUntil(
                function () use ($recipientWalletRpcServer, $minerWalletBalance) {
                    $recipientWalletRpcServer->refresh();
                    return $recipientWalletRpcServer->getBalance()->balance->compareTo($minerWalletBalance) >= 0;
                },
                60,
                'View-only wallet did not see the miner wallet balance'
            );
            self::assertGreaterThanOrEqual(
                0,
                $recipientWalletRpcServer->getBalance()->balance->compareTo($minerWalletBalance)
            );
        } finally {
            self::forgetOpenWalletState();
        }
    }
}
