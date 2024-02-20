<?php

/**
 * Contract tests baseclass.
 *
 * @package brianhenryie/bh-php-monero-daemon-rpc
 */

namespace BrianHenryIE\MoneroRpc;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 */
abstract class ContractTestCase extends TestCase
{
    protected static Wallet $wallet;
    protected static Daemon $rpcClient;

    /**
     * moneord --rpc-bind-port arg (=18081, 28081 if 'testnet', 38081 if 'stagenet')
     */
    public static function setUpBeforeClass(): void
    {
        self::$rpcClient = new Daemon(
            new HttpFactory(),
            new HttpFactory(),
            new Client(),
            new HttpFactory(),
            '127.0.0.1',
            //            Daemon::TESTNET_PORT,
            48089, // see docker-compose.yml
            false
        );
        self::$wallet = new Wallet(
            new HttpFactory(),
            new HttpFactory(),
            new Client(),
            new HttpFactory(),
            '127.0.0.1',
            58083,
            false,
        );
        try {
            self::$rpcClient->miningStatus();
        } catch ( Exception $exception) {
            self::markTestSkipped('Daemon not running.');
        }
        try {
            self::$wallet->getVersion();
        } catch ( Exception $exception) {
            self::markTestSkipped('Wallet not running.');
        }
    }

    protected function getDaemonRpcClient(): Daemon
    {
        return self::$rpcClient;
    }

    protected function getWalletRpcClient(): Wallet
    {
        return self::$wallet;
    }

    /**
     * E.g. `monerod --testnet print_block`
     */
    protected function extractFromCli(string $monerodCliCommand, string $regex): string
    {
        $shell = shell_exec("monerod --stagenet {$monerodCliCommand} --rpc-bind-port 48089");

        $shell = trim(
            $this->stripAnsi(
                $shell ?? ''
            )
        );

        if (strpos($shell, 'Error: Unsuccessful') !== false) {
            throw new Exception('Error: Unsuccessful');
        }

        preg_match($regex, $shell, $matches);

        return $matches[1];
    }

    /**
     * Surprisingly, there is nothing on Packagist to remove ANSI codes from a string.
     */
    private function stripAnsi(string $from): string
    {
        $ansi = [
            '[0;36m',
            '[0m',
            '[?2004h',
            '[?2004l',
        ];

        return str_replace($ansi, '', $from);
    }
}
