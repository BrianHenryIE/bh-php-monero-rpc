<?php

/**
 * DESTRUCTIVE tests: stop the sacrificial services and assert they stay down.
 *
 * Test methods run in definition order. `stopWallet` (added in a later phase) runs BEFORE
 * `stopDaemon`, because a wallet-rpc whose daemon has been killed is not a clean target.
 * `stopDaemon` runs LAST — nothing in this suite may depend on the sacrificial stack after it.
 *
 * @package brianhenryie/bh-php-monero-rpc
 */

namespace BrianHenryIE\MoneroRpc;

use BrianHenryIE\MoneroRpc\Daemon\ResponseBase;
use Exception;

class StopServicesDestructiveIntegrationTest extends MoneroDestructiveIntegrationTestCase
{
    public function testStopWallet(): void
    {
        // Precondition: the sacrificial wallet-rpc is up.
        self::assertGreaterThan(0, self::$sacrificialWalletRpcClient->getVersion()->version);

        // stop_wallet requires an open wallet ("No wallet file" otherwise), so create one.
        self::$sacrificialWalletRpcClient->createWallet(uniqid('sacrificial_'), 'pw');

        // The simple-monero-wallet-rpc image exits its process on stop_wallet.
        self::$sacrificialWalletRpcClient->stopWallet();

        self::pollUntil(
            function () {
                try {
                    self::$sacrificialWalletRpcClient->getVersion();
                    return false;
                } catch (Exception $e) {
                    return true; // connection refused → stopped
                }
            },
            30,
            'Sacrificial wallet-rpc still reachable after stop_wallet'
        );

        $this->expectException(Exception::class);
        self::$sacrificialWalletRpcClient->getVersion();
    }

    public function testStopDaemon(): void
    {
        // Precondition: the sacrificial daemon is up (setUpBeforeClass already asserted this).
        self::assertSame(1, self::$sacrificialDaemonRpcClient->getHeight()->height);

        $result = self::$sacrificialDaemonRpcClient->stopDaemon();

        self::assertInstanceOf(ResponseBase::class, $result);
        self::assertSame('OK', $result->status);

        // The daemon (restart: "no") must now be unreachable.
        self::pollUntil(
            function () {
                try {
                    self::$sacrificialDaemonRpcClient->getHeight();
                    return false; // still up
                } catch (Exception $e) {
                    return true; // connection refused → stopped
                }
            },
            30,
            'Sacrificial daemon still reachable after stop_daemon'
        );

        $this->expectException(Exception::class);
        self::$sacrificialDaemonRpcClient->getHeight();
    }
}
