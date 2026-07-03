<?php

/**
 * Read-only integration tests for the Daemon RPC client against the seeded
 * deterministic regtest chain. Nothing in this class may mutate chain,
 * daemon, or wallet state.
 *
 * `Daemon::stopDaemon()` is intentionally NOT tested here: it would kill the
 * shared container. It has unit coverage only.
 *
 * @package brianhenryie/bh-php-monero-rpc
 */

namespace BrianHenryIE\MoneroRpc;

use BrianHenryIE\MoneroRpc\Daemon\KeyImageSpentStatus;
use BrianHenryIE\MoneroRpc\Daemon\NetType;
use BrianHenryIE\MoneroRpc\Daemon\SendRawTransactionResult;

/**
 * @coversDefaultClass \BrianHenryIE\MoneroRpc\Daemon
 */
class MoneroDaemonRpcIntegrationTest extends MoneroRpcIntegrationTestCase
{
    public function testGetBlockCount(): void
    {
        $result = self::$daemonPrimaryRpcClient->getBlockCount();

        self::assertSame(MoneroRegtestFixture::EXPECTED_CHAIN_HEIGHT_AFTER_SEED, $result->count);
    }

    public function testGetHeightOnPrimaryDaemon(): void
    {
        $result = self::$daemonPrimaryRpcClient->getHeight();

        self::assertSame(MoneroRegtestFixture::EXPECTED_CHAIN_HEIGHT_AFTER_SEED, $result->height);
    }

    /**
     * The peer daemon never had blocks submitted to it directly; matching
     * height proves p2p sync between the two daemons worked.
     */
    public function testGetHeightOnPeerDaemonMatchesPrimary(): void
    {
        $result = self::$daemonPeerRpcClient->getHeight();

        self::assertSame(MoneroRegtestFixture::EXPECTED_CHAIN_HEIGHT_AFTER_SEED, $result->height);
    }

    public function testOnGetBlockHashGenesisIsRegtestConstant(): void
    {
        $result = self::$daemonPrimaryRpcClient->onGetBlockHash(0);

        self::assertSame(MoneroRegtestFixture::REGTEST_GENESIS_BLOCK_HASH, $result);
    }

    public function testOnGetBlockHashMatchesManifest(): void
    {
        $result = self::$daemonPrimaryRpcClient->onGetBlockHash(1);

        self::assertSame(self::$manifest['block_hashes_by_height']['1'], $result);
    }

    public function testGetLastBlockHeader(): void
    {
        $topHeight = MoneroRegtestFixture::EXPECTED_CHAIN_HEIGHT_AFTER_SEED - 1;

        $result = self::$daemonPrimaryRpcClient->getLastBlockHeader();

        self::assertSame($topHeight, $result->blockHeader->height);
        self::assertSame(
            self::$manifest['block_hashes_by_height'][(string) $topHeight],
            $result->blockHeader->hash
        );
        self::assertSame(0, $result->blockHeader->depth);
        self::assertFalse($result->blockHeader->reward->isZero());
    }

    public function testGetBlockHeaderByHeightMatchesManifest(): void
    {
        $height = MoneroRegtestFixture::SEED_BLOCKS_MINED_BEFORE_TRANSFER;

        $result = self::$daemonPrimaryRpcClient->getBlockHeaderByHeight($height);

        self::assertSame($height, $result->blockHeader->height);
        self::assertSame(
            self::$manifest['block_hashes_by_height'][(string) $height],
            $result->blockHeader->hash
        );
    }

    public function testGetBlockHeaderByHashRoundTrip(): void
    {
        $height = MoneroRegtestFixture::SEED_BLOCKS_MINED_BEFORE_TRANSFER;
        $hash = self::$manifest['block_hashes_by_height'][(string) $height];

        $result = self::$daemonPrimaryRpcClient->getBlockHeaderByHash($hash);

        self::assertSame($height, $result->blockHeader->height);
        self::assertSame($hash, $result->blockHeader->hash);
    }

    /**
     * The first block mined on a fresh regtest chain always carries the same
     * reward: the emission curve depends only on already-generated coins.
     */
    public function testFirstBlockRewardIsDeterministic(): void
    {
        $result = self::$daemonPrimaryRpcClient->getBlockHeaderByHeight(1);

        self::assertSame(
            MoneroRegtestFixture::EXPECTED_FIRST_BLOCK_REWARD_ATOMIC_UNITS,
            $result->blockHeader->reward->toAtomicUnitsString()
        );
    }

    public function testGetBlockByHeight(): void
    {
        $height = MoneroRegtestFixture::SEED_BLOCKS_MINED_BEFORE_TRANSFER;

        $result = self::$daemonPrimaryRpcClient->getBlockByHeight($height);

        self::assertSame(
            self::$manifest['block_hashes_by_height'][(string) $height],
            $result->blockHeader->hash
        );
        self::assertNotEmpty($result->blob);
        self::assertNotEmpty($result->minerTxHash);
    }

    public function testGetBlockByHash(): void
    {
        $height = MoneroRegtestFixture::SEED_BLOCKS_MINED_BEFORE_TRANSFER;
        $hash = self::$manifest['block_hashes_by_height'][(string) $height];

        $result = self::$daemonPrimaryRpcClient->getBlockByHash($hash);

        self::assertSame($height, $result->blockHeader->height);
    }

    public function testGetBlockTemplate(): void
    {
        $result = self::$daemonPrimaryRpcClient->getBlockTemplate(
            MoneroRegtestFixture::MINER_WALLET_PRIMARY_ADDRESS,
            8
        );

        self::assertSame(MoneroRegtestFixture::EXPECTED_CHAIN_HEIGHT_AFTER_SEED, $result->height);
        $topHeight = MoneroRegtestFixture::EXPECTED_CHAIN_HEIGHT_AFTER_SEED - 1;
        self::assertSame(
            self::$manifest['block_hashes_by_height'][(string) $topHeight],
            $result->prevHash
        );
        self::assertNotEmpty($result->blocktemplateBlob);
    }

    public function testGetInfo(): void
    {
        $result = self::$daemonPrimaryRpcClient->getInfo();

        self::assertSame(MoneroRegtestFixture::EXPECTED_CHAIN_HEIGHT_AFTER_SEED, $result->height);
        self::assertSame(NetType::Fakechain, $result->nettype);
        self::assertFalse($result->mainnet);
    }

    /**
     * The two daemons are exclusively peered; each must see the other.
     */
    public function testGetConnectionsSeesPeerDaemon(): void
    {
        $primaryResult = self::$daemonPrimaryRpcClient->getConnections();
        $peerResult = self::$daemonPeerRpcClient->getConnections();

        self::assertGreaterThanOrEqual(1, count($primaryResult->connections));
        self::assertGreaterThanOrEqual(1, count($peerResult->connections));
    }

    public function testGetPeerList(): void
    {
        $result = self::$daemonPrimaryRpcClient->getPeerList();

        self::assertSame('OK', $result->status);
    }

    public function testGetTransactionsFindsSeedTransfer(): void
    {
        $result = self::$daemonPrimaryRpcClient->getTransactions([self::$manifest['transfer_txid']]);

        self::assertCount(1, $result->txs);
        self::assertSame(self::$manifest['transfer_txid'], $result->txs[0]->txHash);
        self::assertSame(self::$manifest['transfer_block_height'], $result->txs[0]->blockHeight);
        self::assertFalse($result->txs[0]->inPool);
        self::assertNotNull($result->txs[0]->blockTimestamp);
    }

    public function testGetTransactionPoolStatsIsEmpty(): void
    {
        $result = self::$daemonPrimaryRpcClient->getTransactionPoolStats();

        self::assertSame(0, $result->poolStats->txsTotal);
    }

    public function testGetAltBlocksHashes(): void
    {
        $result = self::$daemonPrimaryRpcClient->getAltBlocksHashes();

        self::assertSame('OK', $result->status);
    }

    /**
     * The exact default limits change between monerod versions (8192 down in
     * v0.18.3, 32768 in v0.18.5), so only their presence is asserted here;
     * exact set/get round-tripping is covered in the mutating suite.
     */
    public function testGetLimit(): void
    {
        $result = self::$daemonPrimaryRpcClient->getLimit();

        self::assertGreaterThan(0, $result->limitDown);
        self::assertGreaterThan(0, $result->limitUp);
    }

    public function testInPeers(): void
    {
        $result = self::$daemonPrimaryRpcClient->inPeers();

        self::assertSame('OK', $result->status);
    }

    public function testGetBansIsEmpty(): void
    {
        $result = self::$daemonPrimaryRpcClient->getBans();

        self::assertSame('OK', $result->status);
    }

    /**
     * Regtest activates every hard fork at the start of the chain.
     */
    public function testGetHardForkInfo(): void
    {
        $result = self::$daemonPrimaryRpcClient->getHardForkInfo();

        self::assertSame(16, $result->version);
        self::assertTrue($result->enabled);
        self::assertSame(1, $result->earliestHeight);
        self::assertSame('OK', $result->status);
    }

    public function testIsKeyImageSpent(): void
    {
        // Recover the key image the seed transfer spent, from the transaction's first input.
        $txResult = self::$daemonPrimaryRpcClient->getTransactions([self::$manifest['transfer_txid']]);
        $txJson = json_decode($txResult->txs[0]->asJson, true);
        $spentKeyImage = $txJson['vin'][0]['key']['k_image'];

        // A random key image that has never been spent.
        $unspentKeyImage = '8d1bd8181bf7d857bdb281e0153d84cd55a3fcaa57c3e570f4a49f935850b5e3';

        $result = self::$daemonPrimaryRpcClient->isKeyImageSpent([$spentKeyImage, $unspentKeyImage]);

        self::assertSame(
            [KeyImageSpentStatus::SpentInBlockchain, KeyImageSpentStatus::Unspent],
            $result->spentStatus
        );
    }

    /**
     * ERROR-CONTRACT: a malformed block blob is rejected with a JSON-RPC error
     * ("Wrong block blob"), which RpcClient surfaces as an exception.
     */
    public function testSubmitBlockRejectsMalformedBlob(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Wrong block blob');

        self::$daemonPrimaryRpcClient->submitBlock('0101');
    }

    /**
     * ERROR-CONTRACT: malformed tx hex is rejected with status "Failed" (not an RPC
     * error envelope), so no exception is thrown — pin that contract.
     */
    public function testSendRawTransactionRejectsGarbageHex(): void
    {
        $result = self::$daemonPrimaryRpcClient->sendRawTransaction('zz');

        self::assertInstanceOf(SendRawTransactionResult::class, $result);
        self::assertSame('Failed', $result->status);
    }

    public function testGetOuts(): void
    {
        // Output (amount 0, global index 0) is the genesis coinbase RingCT output.
        $result = self::$daemonPrimaryRpcClient->getOuts([['amount' => 0, 'index' => 0]], true);

        self::assertCount(1, $result->outs);
        self::assertSame(1, $result->outs[0]->height);
        self::assertNotEmpty($result->outs[0]->key);
        self::assertNotEmpty($result->outs[0]->txid);
        self::assertTrue($result->outs[0]->unlocked);
    }

    public function testMiningStatusIsNotMining(): void
    {
        $result = self::$daemonPrimaryRpcClient->miningStatus();

        self::assertFalse($result->active);
    }

    public function testSaveBc(): void
    {
        $result = self::$daemonPrimaryRpcClient->saveBc();

        self::assertSame('OK', $result->status);
    }
}
