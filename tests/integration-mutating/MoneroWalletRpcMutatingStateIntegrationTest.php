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

            $address = $walletRpcClient->getAddress()->getAddress();
            self::assertNotEmpty($address);

            $walletRpcClient->closeWallet();
            $walletRpcClient->openWallet($throwawayWalletFilename, 'first-password');

            $walletRpcClient->changeWalletPassword('first-password', 'second-password');

            $walletRpcClient->closeWallet();
            $walletRpcClient->openWallet($throwawayWalletFilename, 'second-password');

            self::assertSame($address, $walletRpcClient->getAddress()->getAddress());
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
        $transferAmountAtomicUnits = 500000000000; // 0.5 XMR.

        $recipientWallet = $this->openRecipientWallet();
        $recipientWallet->refresh();
        $recipientBalanceBefore = $recipientWallet->getBalance()->getBalance();

        $minerWallet = $this->openMinerWallet();
        $minerWallet->refresh();

        $transferResult = $minerWallet->transfer(
            '0.5',
            MoneroRegtestFixture::RECIPIENT_WALLET_PRIMARY_ADDRESS
        );

        self::assertSame($transferAmountAtomicUnits, (int) $transferResult->amount);
        self::assertNotEmpty($transferResult->txHash);

        // Confirm the transfer.
        self::$daemonPrimaryRpcClient->generateBlocks(
            10,
            MoneroRegtestFixture::MINER_WALLET_PRIMARY_ADDRESS,
            '',
            0
        );

        $expectedRecipientBalance = $recipientBalanceBefore + $transferAmountAtomicUnits;
        self::pollUntil(
            function () use ($recipientWallet, $expectedRecipientBalance) {
                $recipientWallet->refresh();
                return $recipientWallet->getBalance()->getBalance() === $expectedRecipientBalance;
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
            '0.1',
            MoneroRegtestFixture::RECIPIENT_WALLET_PRIMARY_ADDRESS
        );

        self::assertNotEmpty($result->txHashList);

        // Confirm, so later tests start with an empty tx pool.
        self::$daemonPrimaryRpcClient->generateBlocks(
            10,
            MoneroRegtestFixture::MINER_WALLET_PRIMARY_ADDRESS,
            '',
            0
        );
    }

    public function testSweepDustFindsNoDust(): void
    {
        $this->expectNotToPerformAssertions();

        $minerWallet = $this->openMinerWallet();
        $minerWallet->refresh();

        // Modern chains produce no unmixable dust; asserts only that the call succeeds.
        $minerWallet->sweepDust();
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
        self::assertGreaterThanOrEqual(0, $refreshResult->getBlocksFetched());

        $minerWallet->rescanSpent();

        $minerWallet->rescanBlockchain();

        // After a rescan the wallet must converge back to the daemon height.
        $daemonHeight = self::$daemonPrimaryRpcClient->getHeight()->getHeight();
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
                sslSupport: 'disabled'
            );

            $recipientWallet->refresh();

            self::assertSame(
                self::$daemonPrimaryRpcClient->getHeight()->getHeight(),
                $recipientWallet->getHeight()
            );
        } finally {
            $recipientWallet->setDaemon(
                host: getenv('MONERO_DAEMON_PEER_INTERNAL_HOST')
                    ?: MoneroRegtestFixture::DAEMON_PEER_INTERNAL_HOST,
                port: (int) (getenv('MONERO_DAEMON_INTERNAL_RPC_PORT')
                    ?: MoneroRegtestFixture::DAEMON_INTERNAL_RPC_PORT),
                isTrusted: true,
                sslSupport: 'disabled'
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
        $minerWalletBalance = $minerWallet->getBalance()->getBalance();
        $minerWalletViewKey = $minerWallet->queryKey('view_key')->getKey();

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
                    return $recipientWalletRpcServer->getBalance()->getBalance() >= $minerWalletBalance;
                },
                60,
                'View-only wallet did not see the miner wallet balance'
            );
            self::assertGreaterThanOrEqual(
                $minerWalletBalance,
                $recipientWalletRpcServer->getBalance()->getBalance()
            );
        } finally {
            self::forgetOpenWalletState();
        }
    }
}
