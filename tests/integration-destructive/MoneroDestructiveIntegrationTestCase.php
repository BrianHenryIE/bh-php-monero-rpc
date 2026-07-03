<?php

/**
 * Base class for the `integration-destructive` suite: LIVE tests that STOP a
 * sacrificial daemon / wallet-rpc and assert they stay down.
 *
 * These run against dedicated throwaway containers (`monero-daemon-sacrificial`,
 * `monero-wallet-rpc-sacrificial`, both `restart: "no"`) so killing them cannot
 * affect the seeded stack the other suites depend on. This suite runs LAST in
 * `composer test-integration`.
 *
 * Because a prior destructive test may already have killed a service, the setup
 * FAILS (never skips) with an actionable "recreate the stack" message rather than
 * silently passing — consistent with the other integration suites.
 *
 * @package brianhenryie/bh-php-monero-rpc
 */

namespace BrianHenryIE\MoneroRpc;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use PHPUnit\Framework\TestCase;

abstract class MoneroDestructiveIntegrationTestCase extends TestCase
{
    /**
     * Sacrificial service connection details. Kept out of {@see MoneroRegtestFixture}
     * (which is frozen for determinism) since these containers are unseeded infra.
     */
    public const DAEMON_SACRIFICIAL_RPC_HOST = '127.0.0.1';
    public const DAEMON_SACRIFICIAL_RPC_PORT = 28089;
    public const WALLET_RPC_SACRIFICIAL_HOST = '127.0.0.1';
    public const WALLET_RPC_SACRIFICIAL_PORT = 58085;

    protected static Daemon $sacrificialDaemonRpcClient;
    protected static Wallet $sacrificialWalletRpcClient;

    public static function setUpBeforeClass(): void
    {
        $httpFactory = new HttpFactory();

        self::$sacrificialDaemonRpcClient = new Daemon(
            $httpFactory,
            $httpFactory,
            new Client(),
            $httpFactory,
            getenv('MONERO_DAEMON_SACRIFICIAL_RPC_HOST') ?: self::DAEMON_SACRIFICIAL_RPC_HOST,
            (int) (getenv('MONERO_DAEMON_SACRIFICIAL_RPC_PORT') ?: self::DAEMON_SACRIFICIAL_RPC_PORT),
            false
        );
        self::$sacrificialWalletRpcClient = new Wallet(
            $httpFactory,
            $httpFactory,
            new Client(),
            $httpFactory,
            getenv('MONERO_WALLET_RPC_SACRIFICIAL_HOST') ?: self::WALLET_RPC_SACRIFICIAL_HOST,
            (int) (getenv('MONERO_WALLET_RPC_SACRIFICIAL_PORT') ?: self::WALLET_RPC_SACRIFICIAL_PORT),
            false
        );

        try {
            self::$sacrificialDaemonRpcClient->getHeight();
        } catch (Exception $exception) {
            self::fail(
                'monero-daemon-sacrificial RPC unreachable on '
                . self::DAEMON_SACRIFICIAL_RPC_HOST . ':' . self::DAEMON_SACRIFICIAL_RPC_PORT
                . '. A prior destructive test may have stopped it (restart: "no"). Recreate the stack: '
                . '`make integration-down && make integration-up`. '
                . $exception->getMessage()
            );
        }
    }

    /**
     * @param callable():bool $condition
     */
    protected static function pollUntil(callable $condition, int $timeoutSeconds, string $failureMessage): void
    {
        $start = time();
        while (time() - $start < $timeoutSeconds) {
            try {
                if ($condition()) {
                    return;
                }
            } catch (Exception $exception) {
                // Not ready yet.
            }
            usleep(250000);
        }
        self::fail("Timeout after {$timeoutSeconds}s: {$failureMessage}");
    }
}
