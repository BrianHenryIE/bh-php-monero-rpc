<?php

/**
 * Integration tests for Daemon RPC methods which mutate chain or daemon state.
 *
 * These run AFTER the read-only `integration` suite (see composer
 * `test-integration`). Expectations are re-derived from RPC reads, never from
 * the seeded-chain constants, because earlier tests in this suite extend the
 * chain. Daemon settings are restored in `finally`.
 *
 * `Daemon::stopDaemon()` is deliberately untested here: it would kill the
 * shared container.
 *
 * @package brianhenryie/bh-php-monero-rpc
 */

namespace BrianHenryIE\MoneroRpc;

use BrianHenryIE\MoneroRpc\Daemon\LogCategories;
use BrianHenryIE\MoneroRpc\Daemon\OutPeers;
use BrianHenryIE\MoneroRpc\Daemon\ResponseBase;
use BrianHenryIE\MoneroRpc\Daemon\SendRawTransactionResult;
use BrianHenryIE\MoneroRpc\Daemon\SubmitBlockResult;
use BrianHenryIE\MoneroRpc\Daemon\TransactionPool;

/**
 * @coversDefaultClass \BrianHenryIE\MoneroRpc\Daemon
 */
class MoneroDaemonRpcMutatingStateIntegrationTest extends MoneroRpcIntegrationTestCase
{
    protected static function isReadOnlyTestSuite(): bool
    {
        return false;
    }

    public function testGenerateBlocksExtendsChainOnBothDaemons(): void
    {
        $heightBefore = self::$daemonPrimaryRpcClient->getHeight()->height;

        $result = self::$daemonPrimaryRpcClient->generateBlocks(
            1,
            MoneroRegtestFixture::MINER_WALLET_PRIMARY_ADDRESS,
            '',
            0
        );

        // `generateblocks` returns the height (index) of the last generated
        // block, which equals the pre-mining `get_height` value.
        self::assertSame($heightBefore, $result->height);
        self::assertCount(1, $result->blocks);
        self::assertSame(
            $heightBefore + 1,
            self::$daemonPrimaryRpcClient->getHeight()->height
        );
        self::pollUntil(
            fn() => self::$daemonPeerRpcClient->getHeight()->height === $heightBefore + 1,
            60,
            'monero-daemon-peer did not sync the newly generated block'
        );
    }

    public function testStartMiningMiningStatusStopMining(): void
    {
        try {
            $startResult = self::$daemonPeerRpcClient->startMining(
                backgroundMining: false,
                ignoreBattery: true,
                minerAddress: MoneroRegtestFixture::MINER_WALLET_PRIMARY_ADDRESS,
                threadsCount: 1
            );
            self::assertInstanceOf(ResponseBase::class, $startResult);
            self::assertSame('OK', $startResult->status);

            self::pollUntil(
                fn() => self::$daemonPeerRpcClient->miningStatus()->active,
                30,
                'Mining did not become active after start_mining'
            );
            self::assertTrue(self::$daemonPeerRpcClient->miningStatus()->active);
            self::assertSame(
                MoneroRegtestFixture::MINER_WALLET_PRIMARY_ADDRESS,
                self::$daemonPeerRpcClient->miningStatus()->address
            );

            // set_log_hash_rate only returns OK while the daemon is mining ("NOT MINING"
            // otherwise), so exercise it here where mining is active.
            $logHashRateResult = self::$daemonPeerRpcClient->setLogHashRate(true);
            self::assertInstanceOf(ResponseBase::class, $logHashRateResult);
            self::assertSame('OK', $logHashRateResult->status);
            self::$daemonPeerRpcClient->setLogHashRate(false);
        } finally {
            // NB: stop_mining blocks while the miner thread shuts down (~20s observed).
            self::$daemonPeerRpcClient->stopMining();
        }

        self::pollUntil(
            fn() => self::$daemonPeerRpcClient->miningStatus()->active === false,
            30,
            'Mining still active after stop_mining'
        );
        self::assertFalse(self::$daemonPeerRpcClient->miningStatus()->active);

        // Mining at fixed difficulty 1 may have found blocks; sync the daemons
        // before any later test reads heights.
        self::pollUntil(
            function () {
                return self::$daemonPeerRpcClient->getHeight()->height
                    === self::$daemonPrimaryRpcClient->getHeight()->height;
            },
            60,
            'Daemons did not converge to the same height after mining'
        );
    }

    public function testSetLimitRoundTrip(): void
    {
        $initialLimitResult = self::$daemonPrimaryRpcClient->getLimit();
        $initialLimitDown = $initialLimitResult->limitDown;
        $initialLimitUp = $initialLimitResult->limitUp;

        try {
            $setResult = self::$daemonPrimaryRpcClient->setLimit(4096, 1024);

            self::assertSame(4096, $setResult->limitDown);
            self::assertSame(1024, $setResult->limitUp);

            $getResult = self::$daemonPrimaryRpcClient->getLimit();

            self::assertSame(4096, $getResult->limitDown);
            self::assertSame(1024, $getResult->limitUp);
        } finally {
            self::$daemonPrimaryRpcClient->setLimit($initialLimitDown, $initialLimitUp);
        }

        $restoredResult = self::$daemonPrimaryRpcClient->getLimit();

        self::assertSame($initialLimitDown, $restoredResult->limitDown);
    }

    public function testOutPeersRoundTrip(): void
    {
        try {
            $result = self::$daemonPrimaryRpcClient->outPeers(8);

            self::assertInstanceOf(OutPeers::class, $result);
            self::assertSame(8, $result->outPeers);
            self::assertSame('OK', $result->status);
        } finally {
            // Restore monerod's default so outbound peering is unconstrained for later tests.
            self::$daemonPrimaryRpcClient->outPeers(12);
        }
    }

    /**
     * At --fixed-difficulty 1 the unmodified block template satisfies PoW, so submitting it
     * mines a real block. (If this proves flaky across monerod versions, the malformed-blob
     * ERROR-CONTRACT test in the read-only suite still pins submitBlock's failure behaviour.)
     */
    public function testSubmitBlockAcceptsTemplateBlob(): void
    {
        $template = self::$daemonPrimaryRpcClient->getBlockTemplate(
            MoneroRegtestFixture::MINER_WALLET_PRIMARY_ADDRESS,
            8
        );

        $result = self::$daemonPrimaryRpcClient->submitBlock($template->blocktemplateBlob);

        self::assertInstanceOf(SubmitBlockResult::class, $result);
        self::assertSame('OK', $result->status);
        self::assertNotEmpty($result->blockId);

        self::pollUntil(
            function () {
                return self::$daemonPeerRpcClient->getHeight()->height
                    === self::$daemonPrimaryRpcClient->getHeight()->height;
            },
            60,
            'Daemons did not converge after submitBlock'
        );
    }

    public function testSendRawTransactionBroadcastsAndConfirms(): void
    {
        $minerWallet = $this->openMinerWallet();
        $minerWallet->refresh();

        try {
            // Build a signed tx WITHOUT relaying it, and get its raw hex.
            $built = $minerWallet->transfer(
                MoneroAmount::fromXmr('0.01'),
                MoneroRegtestFixture::RECIPIENT_WALLET_PRIMARY_ADDRESS,
                doNotRelay: true,
                getTxHex: true
            );

            $result = self::$daemonPrimaryRpcClient->sendRawTransaction($built->txBlob);

            self::assertInstanceOf(SendRawTransactionResult::class, $result);
            self::assertSame('OK', $result->status);
            self::assertFalse($result->notRelayed);
            self::assertFalse($result->doubleSpend);

            self::pollUntil(
                fn() => count(self::$daemonPrimaryRpcClient->getTransactionPool()->transactions) > 0,
                30,
                'Broadcast raw transaction did not reach the pool'
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
                'Daemons did not converge after broadcasting the raw transaction'
            );
        }
    }

    public function testGetTransactionPoolSeesUnminedTransfer(): void
    {
        $minerWallet = $this->openMinerWallet();
        $minerWallet->refresh();

        try {
            // Relay (do not mine) a transfer so it sits in the pool.
            $transfer = $minerWallet->transfer(
                MoneroAmount::fromXmr('0.01'),
                MoneroRegtestFixture::RECIPIENT_WALLET_PRIMARY_ADDRESS
            );
            $txid = $transfer->txHash;

            self::pollUntil(
                fn() => count(self::$daemonPrimaryRpcClient->getTransactionPool()->transactions) > 0,
                30,
                'Transfer did not appear in the transaction pool'
            );

            $pool = self::$daemonPrimaryRpcClient->getTransactionPool();
            self::assertInstanceOf(TransactionPool::class, $pool);
            $ids = array_map(fn($tx) => $tx->idHash, $pool->transactions);
            self::assertContains($txid, $ids);

            $entry = $pool->transactions[array_search($txid, $ids, true)];
            self::assertFalse($entry->fee->isZero());
            self::assertNotNull($entry->receiveTime);
        } finally {
            // Confirm the pooled tx and drain the pool for later tests.
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
                'Daemons did not converge after confirming the pool transfer'
            );
        }
    }

    public function testSetLogLevel(): void
    {
        try {
            $result = self::$daemonPrimaryRpcClient->setLogLevel(1);

            self::assertInstanceOf(ResponseBase::class, $result);
            self::assertSame('OK', $result->status);
        } finally {
            self::$daemonPrimaryRpcClient->setLogLevel(0);
        }
    }

    public function testSetLogCategories(): void
    {
        try {
            $result = self::$daemonPrimaryRpcClient->setLogCategories('*:WARNING');

            self::assertInstanceOf(LogCategories::class, $result);
            self::assertSame('*:WARNING', $result->categories);
            self::assertSame('OK', $result->status);
        } finally {
            self::$daemonPrimaryRpcClient->setLogCategories('');
        }
    }

    public function testSetAndUnsetBans(): void
    {
        try {
            $setResult = self::$daemonPrimaryRpcClient->setBans([
                [
                    'host' => '192.0.2.1', // TEST-NET-1, never routable.
                    'ban' => true,
                    'seconds' => 60,
                ],
            ]);

            self::assertInstanceOf(ResponseBase::class, $setResult);
            self::assertSame('OK', $setResult->status);
        } finally {
            self::$daemonPrimaryRpcClient->setBans([
                [
                    'host' => '192.0.2.1',
                    'ban' => false,
                ],
            ]);
        }

        $getBansResult = self::$daemonPrimaryRpcClient->getBans();

        self::assertSame('OK', $getBansResult->status);
    }

    public function testFlushTxPool(): void
    {
        $result = self::$daemonPrimaryRpcClient->flushTxPool(null);

        self::assertInstanceOf(ResponseBase::class, $result);
        self::assertSame('OK', $result->status);
    }
}
