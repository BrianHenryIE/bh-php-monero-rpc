<?php

/**
 * Constants describing the deterministic Monero regtest chain built by
 * `tests/integration/seed-monero-regtest-chain.php` against the topology
 * in `docker-compose.yml`.
 *
 * The mnemonics below are regtest-only test fixtures. They are committed
 * intentionally; the addresses they derive hold no real-world value and
 * never will. Do NOT reuse them outside regtest.
 *
 * Values which are identical on every seed run (heights, addresses, amounts)
 * are constants here. Values which differ per run (block hashes, txids, fees —
 * block headers contain timestamps) are written by the seed script to
 * `tests/_data/integration/manifest.json` and read by tests at runtime.
 *
 * @see INTEGRATION_TEST_PLAN.md
 *
 * @package brianhenryie/bh-php-monero-rpc
 */

namespace BrianHenryIE\MoneroRpc;

class MoneroRegtestFixture
{
    public const DAEMON_PRIMARY_RPC_HOST = '127.0.0.1';
    public const DAEMON_PRIMARY_RPC_PORT = 48089;
    public const DAEMON_PEER_RPC_HOST = '127.0.0.1';
    public const DAEMON_PEER_RPC_PORT = 38089;

    /**
     * Daemon RPC addresses as reachable from *inside* the Docker network.
     *
     * `set_daemon` instructs the wallet-rpc container to connect to a daemon, so
     * it needs an address the container can reach — the host-side 127.0.0.1
     * mappings above are not routable from within a container. These match the
     * `--daemon-address` values in docker-compose.yml.
     */
    public const DAEMON_PRIMARY_INTERNAL_HOST = 'monero-daemon-primary';
    public const DAEMON_PEER_INTERNAL_HOST = 'monero-daemon-peer';
    public const DAEMON_INTERNAL_RPC_PORT = 18081;
    public const WALLET_RPC_MINER_HOST = '127.0.0.1';
    public const WALLET_RPC_MINER_PORT = 58083;
    public const WALLET_RPC_RECIPIENT_HOST = '127.0.0.1';
    public const WALLET_RPC_RECIPIENT_PORT = 58084;

    public const MINER_WALLET_FILENAME = 'miner_wallet';
    public const MINER_WALLET_PASSWORD = 'integration-test-password';
    public const MINER_WALLET_MNEMONIC =
        'buzzer robot maverick doing each coexist remedy dilute tattoo somewhere lullaby kennel '
        . 'unopened donuts occur inroads biweekly under knowledge uphill idled vessel macro midst doing';
    public const MINER_WALLET_PRIMARY_ADDRESS =
        '43zjHrEvKytNk8YG7JEa2M9XJsPtfkG23jFBukXBj43sETTrwZhVYr1Pup4HH9qox9GuNeUuTvquyYc8Sk8PTVSaPbhVN81';

    public const RECIPIENT_WALLET_FILENAME = 'recipient_wallet';
    public const RECIPIENT_WALLET_PASSWORD = 'integration-test-password';
    public const RECIPIENT_WALLET_MNEMONIC =
        'yahoo southern gone bays session portents pitched godfather teardrop eggs axis rodent '
        . 'natural cavernous psychic pencil lesson zones motherly speedy total judge ablaze aerial zones';
    public const RECIPIENT_WALLET_PRIMARY_ADDRESS =
        '4AMjwQbYCwUGJbv5WUwNwV5jj1gTnCnTqRFBugw258F9bhG5RYUkRAj3wCpj4X6rxxcX8vDiWKT77LFz88SFv2WsEiLa8bq';

    /**
     * The regtest genesis block hash. Height 0; identical on every regtest chain.
     */
    public const REGTEST_GENESIS_BLOCK_HASH =
        '418015bb9ae982a1975da7d79277c2705727a56894ba0fb246adaabb1f4632e3';

    /**
     * Coinbase outputs unlock after 60 confirmations, and building a
     * ring-size-16 transaction needs at least 16 unlocked outputs on the
     * chain. 120 blocks leaves 60 unlocked coinbase outputs at transfer time.
     */
    public const SEED_BLOCKS_MINED_BEFORE_TRANSFER = 120;

    /**
     * Confirms the seed transfer well past any lock.
     */
    public const SEED_BLOCKS_MINED_AFTER_TRANSFER = 10;

    /**
     * `get_height` after seeding: genesis (1) + 120 + 10.
     */
    public const EXPECTED_CHAIN_HEIGHT_AFTER_SEED = 131;

    /**
     * 1.23 XMR, transferred miner → recipient during seeding.
     *
     * Atomic-unit amounts are STRINGS so they are exact regardless of magnitude — an
     * atomic-unit value may exceed PHP's signed int64 range (see {@see MoneroAmount}).
     */
    public const TRANSFER_AMOUNT_ATOMIC_UNITS = '1230000000000';

    /**
     * The recipient wallet receives exactly the transfer, nothing else.
     */
    public const EXPECTED_RECIPIENT_BALANCE_ATOMIC_UNITS = self::TRANSFER_AMOUNT_ATOMIC_UNITS;

    /**
     * Block reward of the first mined regtest block (observed; the emission
     * curve starts from zero generated coins). ~35.18 XMR.
     */
    public const EXPECTED_FIRST_BLOCK_REWARD_ATOMIC_UNITS = '35184338534400';

    /**
     * The seed transfer amount (1.23 XMR) as a {@see MoneroAmount}.
     */
    public static function getTransferAmount(): MoneroAmount
    {
        return MoneroAmount::fromAtomicUnits(self::TRANSFER_AMOUNT_ATOMIC_UNITS);
    }

    /**
     * The expected recipient balance after seeding as a {@see MoneroAmount}.
     */
    public static function getExpectedRecipientBalance(): MoneroAmount
    {
        return MoneroAmount::fromAtomicUnits(self::EXPECTED_RECIPIENT_BALANCE_ATOMIC_UNITS);
    }

    /**
     * The first-block reward as a {@see MoneroAmount}.
     */
    public static function getExpectedFirstBlockReward(): MoneroAmount
    {
        return MoneroAmount::fromAtomicUnits(self::EXPECTED_FIRST_BLOCK_REWARD_ATOMIC_UNITS);
    }

    public const MANIFEST_RELATIVE_PATH = '/../_data/integration/manifest.json';

    public static function getManifestPath(): string
    {
        return __DIR__ . self::MANIFEST_RELATIVE_PATH;
    }

    /**
     * @return array<string,mixed> The manifest written by the seed script.
     * @throws \Exception When the manifest is absent, i.e. the seed script has not run.
     */
    public static function readManifest(): array
    {
        $manifestPath = self::getManifestPath();
        if (!file_exists($manifestPath)) {
            throw new \Exception(
                'Integration fixture manifest not found at ' . $manifestPath
                . '. Run `make integration-up && make integration-seed` first.'
            );
        }
        return json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
    }
}
