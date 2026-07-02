<?php

/**
 * Base class for integration tests against the seeded Docker Monero regtest stack.
 *
 * Prerequisites (see Makefile / INTEGRATION_TEST_PLAN.md):
 *
 *     make integration-up
 *     make integration-seed
 *
 * Unlike the retired contract tests, these tests FAIL (never skip) when the
 * stack is unreachable or in an unexpected state, so CI cannot silently pass.
 *
 * State-mutation policy: tests in `tests/integration/` must not change chain,
 * wallet, or daemon state. Tests which do belong in `tests/integration-mutating/`
 * (run after this suite) and must re-derive any expectation that their own
 * mutations invalidate, restoring daemon settings in `finally` where possible.
 *
 * @package brianhenryie/bh-php-monero-rpc
 */

namespace BrianHenryIE\MoneroRpc;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use PHPUnit\Framework\TestCase;

abstract class MoneroRpcIntegrationTestCase extends TestCase
{
    protected static Daemon $daemonPrimaryRpcClient;
    protected static Daemon $daemonPeerRpcClient;
    protected static Wallet $minerWalletRpcClient;
    protected static Wallet $recipientWalletRpcClient;

    /** @var array<string,mixed> */
    protected static array $manifest;

    /**
     * Filename of the wallet currently open on each wallet-rpc server, used to
     * skip redundant (slow) `open_wallet` calls. Null when unknown.
     */
    protected static ?string $minerWalletRpcServerOpenWalletFilename = null;
    protected static ?string $recipientWalletRpcServerOpenWalletFilename = null;

    public static function setUpBeforeClass(): void
    {
        self::$daemonPrimaryRpcClient = self::buildDaemonRpcClient(
            getenv('MONERO_DAEMON_PRIMARY_RPC_HOST') ?: MoneroRegtestFixture::DAEMON_PRIMARY_RPC_HOST,
            (int) (getenv('MONERO_DAEMON_PRIMARY_RPC_PORT') ?: MoneroRegtestFixture::DAEMON_PRIMARY_RPC_PORT)
        );
        self::$daemonPeerRpcClient = self::buildDaemonRpcClient(
            getenv('MONERO_DAEMON_PEER_RPC_HOST') ?: MoneroRegtestFixture::DAEMON_PEER_RPC_HOST,
            (int) (getenv('MONERO_DAEMON_PEER_RPC_PORT') ?: MoneroRegtestFixture::DAEMON_PEER_RPC_PORT)
        );
        self::$minerWalletRpcClient = self::buildWalletRpcClient(
            getenv('MONERO_WALLET_RPC_MINER_HOST') ?: MoneroRegtestFixture::WALLET_RPC_MINER_HOST,
            (int) (getenv('MONERO_WALLET_RPC_MINER_PORT') ?: MoneroRegtestFixture::WALLET_RPC_MINER_PORT)
        );
        self::$recipientWalletRpcClient = self::buildWalletRpcClient(
            getenv('MONERO_WALLET_RPC_RECIPIENT_HOST') ?: MoneroRegtestFixture::WALLET_RPC_RECIPIENT_HOST,
            (int) (getenv('MONERO_WALLET_RPC_RECIPIENT_PORT') ?: MoneroRegtestFixture::WALLET_RPC_RECIPIENT_PORT)
        );

        try {
            self::$manifest = MoneroRegtestFixture::readManifest();
        } catch (Exception $exception) {
            self::fail($exception->getMessage());
        }

        try {
            $height = self::$daemonPrimaryRpcClient->getHeight()->getHeight();
        } catch (Exception $exception) {
            self::fail(
                'monero-daemon-primary RPC unreachable on '
                . MoneroRegtestFixture::DAEMON_PRIMARY_RPC_HOST . ':'
                . MoneroRegtestFixture::DAEMON_PRIMARY_RPC_PORT
                . '. Run `make integration-up && make integration-seed`. '
                . $exception->getMessage()
            );
        }

        if (static::isReadOnlyTestSuite() && $height !== MoneroRegtestFixture::EXPECTED_CHAIN_HEIGHT_AFTER_SEED) {
            self::fail(
                "Chain height {$height} !== expected "
                . MoneroRegtestFixture::EXPECTED_CHAIN_HEIGHT_AFTER_SEED
                . '. The chain is unseeded or was mutated. Run '
                . '`make integration-down && make integration-up && make integration-seed`.'
            );
        }
    }

    /**
     * Overridden (to return false) by mutating-state test classes, which run
     * after other tests may already have extended the chain.
     */
    protected static function isReadOnlyTestSuite(): bool
    {
        return true;
    }

    protected static function buildDaemonRpcClient(string $host, int $port): Daemon
    {
        $httpFactory = new HttpFactory();
        return new Daemon($httpFactory, $httpFactory, new Client(), $httpFactory, $host, $port, false);
    }

    protected static function buildWalletRpcClient(string $host, int $port): Wallet
    {
        $httpFactory = new HttpFactory();
        return new Wallet($httpFactory, $httpFactory, new Client(), $httpFactory, $host, $port, false);
    }

    /**
     * The wallet-rpc server holds one open wallet at a time; the seed script
     * leaves the fixture wallets open, but mutating tests may have switched
     * wallet files, so re-open explicitly before depending on wallet state.
     */
    protected function openMinerWallet(): Wallet
    {
        if (self::$minerWalletRpcServerOpenWalletFilename !== MoneroRegtestFixture::MINER_WALLET_FILENAME) {
            self::$minerWalletRpcClient->openWallet(
                MoneroRegtestFixture::MINER_WALLET_FILENAME,
                MoneroRegtestFixture::MINER_WALLET_PASSWORD
            );
            self::$minerWalletRpcServerOpenWalletFilename = MoneroRegtestFixture::MINER_WALLET_FILENAME;
        }
        return self::$minerWalletRpcClient;
    }

    protected function openRecipientWallet(): Wallet
    {
        if (self::$recipientWalletRpcServerOpenWalletFilename !== MoneroRegtestFixture::RECIPIENT_WALLET_FILENAME) {
            self::$recipientWalletRpcClient->openWallet(
                MoneroRegtestFixture::RECIPIENT_WALLET_FILENAME,
                MoneroRegtestFixture::RECIPIENT_WALLET_PASSWORD
            );
            self::$recipientWalletRpcServerOpenWalletFilename = MoneroRegtestFixture::RECIPIENT_WALLET_FILENAME;
        }
        return self::$recipientWalletRpcClient;
    }

    /**
     * Mutating tests which open a different wallet file MUST call this so the
     * next `openMinerWallet()`/`openRecipientWallet()` re-opens the fixture wallet.
     */
    protected static function forgetOpenWalletState(): void
    {
        self::$minerWalletRpcServerOpenWalletFilename = null;
        self::$recipientWalletRpcServerOpenWalletFilename = null;
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
