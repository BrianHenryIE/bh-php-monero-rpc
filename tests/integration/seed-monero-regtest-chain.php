#!/usr/bin/env php
<?php

/**
 * Builds the deterministic Monero regtest chain the integration tests assert against.
 *
 * Run after `make integration-up` (or against any stack matching docker-compose.yml):
 *
 *     php tests/integration/seed-monero-regtest-chain.php
 *
 * Sequence:
 *   1. Restore the two deterministic wallets from their committed mnemonics.
 *   2. Mine 70 blocks to the miner wallet (coinbase unlocks after 60 confirmations).
 *   3. Wait for the peer daemon to sync.
 *   4. Transfer exactly 1.23 XMR miner → recipient.
 *   5. Mine 10 more blocks to confirm the transfer. Final height: 81.
 *   6. Write run-specific values (hashes, txid, fee) to tests/_data/integration/manifest.json.
 *
 * Idempotent: exits 0 if the chain is already seeded; refuses to run against a
 * chain in any other state (run `make integration-down` first).
 *
 * This script deliberately uses this library's own RPC clients, so a successful
 * seed is itself a smoke test of Daemon and Wallet.
 *
 * @package brianhenryie/bh-php-monero-rpc
 */

declare(strict_types=1);

namespace BrianHenryIE\MoneroRpc;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/MoneroRegtestFixture.php';

if (PHP_SAPI !== 'cli') {
    exit(1);
}

/**
 * @param callable():bool $condition
 */
function pollUntil(callable $condition, int $timeoutSeconds, string $failureMessage): void
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
    fwrite(STDERR, "Timeout: {$failureMessage}\n");
    exit(1);
}

function buildDaemonRpcClient(string $host, int $port): Daemon
{
    $httpFactory = new HttpFactory();
    return new Daemon($httpFactory, $httpFactory, new Client(), $httpFactory, $host, $port, false);
}

function buildWalletRpcClient(string $host, int $port): Wallet
{
    $httpFactory = new HttpFactory();
    return new Wallet($httpFactory, $httpFactory, new Client(), $httpFactory, $host, $port, false);
}

$daemonPrimary = buildDaemonRpcClient(
    getenv('MONERO_DAEMON_PRIMARY_RPC_HOST') ?: MoneroRegtestFixture::DAEMON_PRIMARY_RPC_HOST,
    (int) (getenv('MONERO_DAEMON_PRIMARY_RPC_PORT') ?: MoneroRegtestFixture::DAEMON_PRIMARY_RPC_PORT)
);
$daemonPeer = buildDaemonRpcClient(
    getenv('MONERO_DAEMON_PEER_RPC_HOST') ?: MoneroRegtestFixture::DAEMON_PEER_RPC_HOST,
    (int) (getenv('MONERO_DAEMON_PEER_RPC_PORT') ?: MoneroRegtestFixture::DAEMON_PEER_RPC_PORT)
);
$minerWalletRpc = buildWalletRpcClient(
    getenv('MONERO_WALLET_RPC_MINER_HOST') ?: MoneroRegtestFixture::WALLET_RPC_MINER_HOST,
    (int) (getenv('MONERO_WALLET_RPC_MINER_PORT') ?: MoneroRegtestFixture::WALLET_RPC_MINER_PORT)
);
$recipientWalletRpc = buildWalletRpcClient(
    getenv('MONERO_WALLET_RPC_RECIPIENT_HOST') ?: MoneroRegtestFixture::WALLET_RPC_RECIPIENT_HOST,
    (int) (getenv('MONERO_WALLET_RPC_RECIPIENT_PORT') ?: MoneroRegtestFixture::WALLET_RPC_RECIPIENT_PORT)
);

$manifestPath = MoneroRegtestFixture::getManifestPath();

echo "Waiting for daemons and wallet-rpc servers...\n";
pollUntil(fn() => $daemonPrimary->getHeight()->getHeight() >= 1, 60, 'monero-daemon-primary RPC unreachable');
pollUntil(fn() => $daemonPeer->getHeight()->getHeight() >= 1, 60, 'monero-daemon-peer RPC unreachable');
pollUntil(fn() => $minerWalletRpc->getVersion()->getVersion() > 0, 60, 'monero-wallet-rpc-miner unreachable');
pollUntil(fn() => $recipientWalletRpc->getVersion()->getVersion() > 0, 60, 'monero-wallet-rpc-recipient unreachable');

$initialHeight = $daemonPrimary->getHeight()->getHeight();

if ($initialHeight === MoneroRegtestFixture::EXPECTED_CHAIN_HEIGHT_AFTER_SEED && file_exists($manifestPath)) {
    echo "Chain already seeded (height {$initialHeight}) and manifest present. Nothing to do.\n";
    exit(0);
}
if ($initialHeight !== 1) {
    fwrite(
        STDERR,
        "Chain height is {$initialHeight}; expected 1 (unseeded) or "
        . MoneroRegtestFixture::EXPECTED_CHAIN_HEIGHT_AFTER_SEED
        . " (seeded). Run `make integration-down` for a clean slate.\n"
    );
    exit(1);
}

echo "Restoring deterministic wallets...\n";
$minerRestoreResult = $minerWalletRpc->restoreDeterministicWallet(
    MoneroRegtestFixture::MINER_WALLET_FILENAME,
    MoneroRegtestFixture::MINER_WALLET_PASSWORD,
    MoneroRegtestFixture::MINER_WALLET_MNEMONIC
);
if ($minerRestoreResult->getAddress() !== MoneroRegtestFixture::MINER_WALLET_PRIMARY_ADDRESS) {
    fwrite(STDERR, "Miner wallet restored to unexpected address {$minerRestoreResult->getAddress()}\n");
    exit(1);
}
$recipientRestoreResult = $recipientWalletRpc->restoreDeterministicWallet(
    MoneroRegtestFixture::RECIPIENT_WALLET_FILENAME,
    MoneroRegtestFixture::RECIPIENT_WALLET_PASSWORD,
    MoneroRegtestFixture::RECIPIENT_WALLET_MNEMONIC
);
if ($recipientRestoreResult->getAddress() !== MoneroRegtestFixture::RECIPIENT_WALLET_PRIMARY_ADDRESS) {
    fwrite(STDERR, "Recipient wallet restored to unexpected address {$recipientRestoreResult->getAddress()}\n");
    exit(1);
}

echo 'Mining ' . MoneroRegtestFixture::SEED_BLOCKS_MINED_BEFORE_TRANSFER . " blocks to the miner wallet...\n";
$generateBlocksResult = $daemonPrimary->generateBlocks(
    MoneroRegtestFixture::SEED_BLOCKS_MINED_BEFORE_TRANSFER,
    MoneroRegtestFixture::MINER_WALLET_PRIMARY_ADDRESS,
    '',
    0
);
$heightAfterMining = 1 + MoneroRegtestFixture::SEED_BLOCKS_MINED_BEFORE_TRANSFER;

echo "Waiting for monero-daemon-peer to sync to height {$heightAfterMining}...\n";
pollUntil(
    fn() => $daemonPeer->getHeight()->getHeight() === $heightAfterMining,
    120,
    'monero-daemon-peer did not sync; are the daemons peered?'
);

echo "Refreshing miner wallet...\n";
$minerWalletRpc->refresh();
pollUntil(
    fn() => $minerWalletRpc->getBalance()->getUnlockedBalance() > 0,
    60,
    'Miner wallet shows no unlocked balance after mining'
);

echo "Transferring 1.23 XMR miner -> recipient...\n";
// Wallet::transfer() takes XMR (not atomic units) and converts internally.
$transferResult = $minerWalletRpc->transfer(
    '1.23',
    MoneroRegtestFixture::RECIPIENT_WALLET_PRIMARY_ADDRESS
);
// NB: RpcClient maps response keys to camelCase, even on stdClass results.
$transferTxid = $transferResult->txHash;
$transferTxKey = $transferResult->txKey;
$transferFeeAtomicUnits = (int) $transferResult->fee;
if ((int) $transferResult->amount !== MoneroRegtestFixture::TRANSFER_AMOUNT_ATOMIC_UNITS) {
    fwrite(STDERR, "Transfer amount mismatch: {$transferResult->amount}\n");
    exit(1);
}

echo 'Mining ' . MoneroRegtestFixture::SEED_BLOCKS_MINED_AFTER_TRANSFER . " blocks to confirm the transfer...\n";
$daemonPrimary->generateBlocks(
    MoneroRegtestFixture::SEED_BLOCKS_MINED_AFTER_TRANSFER,
    MoneroRegtestFixture::MINER_WALLET_PRIMARY_ADDRESS,
    '',
    0
);

$expectedFinalHeight = MoneroRegtestFixture::EXPECTED_CHAIN_HEIGHT_AFTER_SEED;
pollUntil(
    fn() => $daemonPeer->getHeight()->getHeight() === $expectedFinalHeight,
    120,
    "monero-daemon-peer did not sync to final height {$expectedFinalHeight}"
);

echo "Refreshing wallets and verifying balances...\n";
$minerWalletRpc->refresh();
$recipientWalletRpc->refresh();
pollUntil(
    fn() => $recipientWalletRpc->getBalance()->getBalance()
        === MoneroRegtestFixture::EXPECTED_RECIPIENT_BALANCE_ATOMIC_UNITS,
    60,
    'Recipient wallet balance is not exactly '
        . MoneroRegtestFixture::EXPECTED_RECIPIENT_BALANCE_ATOMIC_UNITS
);

$transferDetails = $minerWalletRpc->getTransferByTxid($transferTxid);
$transferBlockHeight = (int) $transferDetails->transfer->height;

$minerBalance = $minerWalletRpc->getBalance();

$firstBlockHeader = $daemonPrimary->getBlockHeaderByHeight(1);

$manifest = [
    'seeded_at' => date('c'),
    'monerod_version' => $daemonPrimary->getInfo()->getVersion(),
    'chain_height' => $daemonPrimary->getHeight()->getHeight(),
    'genesis_block_hash' => $daemonPrimary->onGetBlockHash(0),
    'block_hashes_by_height' => [
        '1' => $daemonPrimary->onGetBlockHash(1),
        (string) MoneroRegtestFixture::SEED_BLOCKS_MINED_BEFORE_TRANSFER => $daemonPrimary->onGetBlockHash(
            MoneroRegtestFixture::SEED_BLOCKS_MINED_BEFORE_TRANSFER
        ),
        (string) ($expectedFinalHeight - 1) => $daemonPrimary->onGetBlockHash($expectedFinalHeight - 1),
    ],
    'first_block_reward_atomic_units' => $firstBlockHeader->getBlockHeader()->getReward(),
    'transfer_txid' => $transferTxid,
    'transfer_tx_key' => $transferTxKey,
    'transfer_fee_atomic_units' => $transferFeeAtomicUnits,
    'transfer_block_height' => $transferBlockHeight,
    'transfer_amount_atomic_units' => MoneroRegtestFixture::TRANSFER_AMOUNT_ATOMIC_UNITS,
    'miner_wallet_balance_atomic_units' => $minerBalance->getBalance(),
    'miner_wallet_unlocked_balance_atomic_units' => $minerBalance->getUnlockedBalance(),
];

if (!is_dir(dirname($manifestPath))) {
    mkdir(dirname($manifestPath), 0777, true);
}
file_put_contents(
    $manifestPath,
    json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"
);

echo "Seeded. Manifest written to {$manifestPath}:\n";
echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
exit(0);
