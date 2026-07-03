<?php

/**
 * Phase 1 scaffold: proves the sacrificial stack is wired up and reachable before
 * the real destructive tests (stopDaemon, stopWallet) are added in later phases.
 *
 * The sacrificial daemon is standalone and unseeded, so it sits at the regtest
 * genesis height (1) — distinct from the seeded primary/peer chain.
 *
 * @package brianhenryie/bh-php-monero-rpc
 */

namespace BrianHenryIE\MoneroRpc;

class SacrificialStackSmokeTest extends MoneroDestructiveIntegrationTestCase
{
    public function testSacrificialDaemonIsReachableAndUnseeded(): void
    {
        $height = self::$sacrificialDaemonRpcClient->getHeight()->height;

        self::assertSame(1, $height, 'Sacrificial daemon should be an unseeded regtest chain at genesis height 1.');
    }

    public function testSacrificialWalletRpcIsReachable(): void
    {
        $version = self::$sacrificialWalletRpcClient->getVersion()->version;

        self::assertGreaterThan(0, $version);
    }
}
