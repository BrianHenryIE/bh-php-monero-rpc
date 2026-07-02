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
        $heightBefore = self::$daemonPrimaryRpcClient->getHeight()->getHeight();

        $result = self::$daemonPrimaryRpcClient->generateBlocks(
            1,
            MoneroRegtestFixture::MINER_WALLET_PRIMARY_ADDRESS,
            '',
            0
        );

        // `generateblocks` returns the height (index) of the last generated
        // block, which equals the pre-mining `get_height` value.
        self::assertSame($heightBefore, $result->getHeight());
        self::assertCount(1, $result->getBlocks());
        self::assertSame(
            $heightBefore + 1,
            self::$daemonPrimaryRpcClient->getHeight()->getHeight()
        );
        self::pollUntil(
            fn() => self::$daemonPeerRpcClient->getHeight()->getHeight() === $heightBefore + 1,
            60,
            'monero-daemon-peer did not sync the newly generated block'
        );
    }

    public function testStartMiningMiningStatusStopMining(): void
    {
        try {
            self::$daemonPeerRpcClient->startMining(
                backgroundMining: false,
                ignoreBattery: true,
                minerAddress: MoneroRegtestFixture::MINER_WALLET_PRIMARY_ADDRESS,
                threadsCount: 1
            );

            self::pollUntil(
                fn() => self::$daemonPeerRpcClient->miningStatus()->getActive(),
                30,
                'Mining did not become active after start_mining'
            );
            self::assertTrue(self::$daemonPeerRpcClient->miningStatus()->getActive());
            self::assertSame(
                MoneroRegtestFixture::MINER_WALLET_PRIMARY_ADDRESS,
                self::$daemonPeerRpcClient->miningStatus()->getAddress()
            );
        } finally {
            // NB: stop_mining blocks while the miner thread shuts down (~20s observed).
            self::$daemonPeerRpcClient->stopMining();
        }

        self::pollUntil(
            fn() => self::$daemonPeerRpcClient->miningStatus()->getActive() === false,
            30,
            'Mining still active after stop_mining'
        );
        self::assertFalse(self::$daemonPeerRpcClient->miningStatus()->getActive());

        // Mining at fixed difficulty 1 may have found blocks; sync the daemons
        // before any later test reads heights.
        self::pollUntil(
            function () {
                return self::$daemonPeerRpcClient->getHeight()->getHeight()
                    === self::$daemonPrimaryRpcClient->getHeight()->getHeight();
            },
            60,
            'Daemons did not converge to the same height after mining'
        );
    }

    public function testSetLimitRoundTrip(): void
    {
        $initialLimitResult = self::$daemonPrimaryRpcClient->getLimit();
        $initialLimitDown = $initialLimitResult->getLimitDown();
        $initialLimitUp = $initialLimitResult->getLimitUp();

        try {
            $setResult = self::$daemonPrimaryRpcClient->setLimit(4096, 1024);

            self::assertSame(4096, $setResult->getLimitDown());
            self::assertSame(1024, $setResult->getLimitUp());

            $getResult = self::$daemonPrimaryRpcClient->getLimit();

            self::assertSame(4096, $getResult->getLimitDown());
            self::assertSame(1024, $getResult->getLimitUp());
        } finally {
            self::$daemonPrimaryRpcClient->setLimit($initialLimitDown, $initialLimitUp);
        }

        $restoredResult = self::$daemonPrimaryRpcClient->getLimit();

        self::assertSame($initialLimitDown, $restoredResult->getLimitDown());
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

            // `Daemon::setBans()` is untyped; the response is stdClass.
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

        self::assertSame('OK', $getBansResult->getStatus());
    }

    public function testFlushTxPool(): void
    {
        $result = self::$daemonPrimaryRpcClient->flushTxPool(null);

        self::assertIsObject($result);
        self::assertSame('OK', $result->status);
    }
}
