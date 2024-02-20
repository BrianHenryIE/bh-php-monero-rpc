<?php

/**
 * Contract tests.
 *
 * Ensure behaviour does not change after refactoring.
 *
 * @see https://github.com/monerodocs/md/blob/master/docs/interacting/monerod-reference.md
 * @see https://github.com/monero-project/monero/blob/master/src/daemon/command_server.cpp
 *
 * @package brianhenryie/bh-php-monero-daemon-rpc
 */

namespace BrianHenryIE\MoneroRpc;

/**
 * @coversDefaultClass \BrianHenryIE\MoneroRpc\Daemon
 */
class DaemonContractTest extends ContractTestCase
{
    public function testCollectTestData(): void
    {
        $this->markTestSkipped('only for saving test data');

        $daemonRpc = $this->getDaemonRpcClient();

        // Have data:
//      $daemonRpc->getBans();
//        $daemonRpc->getInfo();
//        $daemonRpc->getBlockHeaderByHeight(2891820);
//        $daemonRpc->getLastBlockHeader();
//        $daemonRpc->getAltBlocksHashes();
//        $daemonRpc->stopMining();
//        $daemonRpc->miningStatus();
//        $daemonRpc->saveBc();
//        $daemonRpc->getPeerList();
//        $daemonRpc->getTransactionPoolStats();
//        $daemonRpc->stopDaemon();
//        $daemonRpc->inPeers();
//        $daemonRpc->getLimit();
//        $daemonRpc->getBlockHeaderByHash('5b544791d319a5ae2551c26dc592ebad0b9afe4fcaf4dc087508f902f1f43670');
//        $daemonRpc->setLimit( 8192, 2048 );

//      // Both returned json_rpc-getblock.json
//        $daemonRpc->getBlockByHeight( 2891820 );
//        $daemonRpc->getBlockByHash('5b544791d319a5ae2551c26dc592ebad0b9afe4fcaf4dc087508f902f1f43670');

        // Need more info:
//        $daemonRpc->isKeyImageSpent();
//        $daemonRpc->setLogCategories();
//        $daemonRpc->getOuts();

//       404
//      $daemonRpc->startSaveGraph();
//        $daemonRpc->stopSaveGraph();
    }

    public function testGetBlockCount(): void
    {
        $daemonRpc = $this->getDaemonRpcClient();

        $height = (int) $this->extractFromCli(
            'print_height',
            '/(\d+)$/'
        );

        $result = $daemonRpc->getBlockCount();

        self::assertSame($height, $result->getCount());
    }

    public function testLimit(): void
    {
        $daemonRpc = $this->getDaemonRpcClient();

        $result = $daemonRpc->getLimit();

        self::assertEquals(8192, $result->getLimitDown());
    }

    public function testGetBans(): void
    {

        $daemonRpc = $this->getDaemonRpcClient();

        $result = $daemonRpc->getBans();

        self::assertEquals('OK', $result->getStatus());
        self::assertEquals(false, $result->getUntrusted());
    }

    public function testGetInfo(): void
    {

        $daemonRpc = $this->getDaemonRpcClient();

        $result = $daemonRpc->getInfo();

        self::assertEquals(600000, $result->getBlockSizeLimit());
        self::assertEquals(false, $result->getUpdateAvailable());
    }

    public function testGetBlock(): void
    {

        $daemonRpc = $this->getDaemonRpcClient();

        $result = $daemonRpc->getBlockByHash('41aea45eb8e6f627f3d9980de9f2048116bec00b4bd15b669d484681e881f6ef');

        self::assertEquals('994854e739b86fe37a057b7e7069b13c62cc0b375d3bd3fa65d8670942a2e4a6', $result->minerTxHash);
    }

    public function testGetBlockHeaderByHash(): void
    {

        $daemonRpc = $this->getDaemonRpcClient();

        $result = $daemonRpc->getBlockHeaderByHash('41aea45eb8e6f627f3d9980de9f2048116bec00b4bd15b669d484681e881f6ef');

        self::assertEquals(18289227130, $result->getBlockHeader()->getCumulativeDifficulty());
        self::assertEquals('994854e739b86fe37a057b7e7069b13c62cc0b375d3bd3fa65d8670942a2e4a6', $result->getBlockHeader()->getMinerTxHash());
        self::assertEquals(false, $result->getBlockHeader()->getOrphanStatus());
    }

    public function testGetMiningStatus(): void
    {

        $daemonRpc = $this->getDaemonRpcClient();

        $result = $daemonRpc->miningStatus();

        self::assertEquals(false, $result->getActive());
        self::assertEquals(false, $result->getIsBackgroundMiningEnabled());
        self::assertEquals(false, $result->getBgIgnoreBattery());
    }

    public function testStopMining(): void
    {

        $daemonRpc = $this->getDaemonRpcClient();

        $result = $daemonRpc->stopMining();

        self::assertEquals('Mining never started', $result->getStatus());
        self::assertEquals(false, $result->getUntrusted());
    }

    public function testOnGetBlockHash(): void
    {
        $daemonRpc = $this->getDaemonRpcClient();

        $height = max(1, $daemonRpc->getBlockCount()->getCount() - 10);

        // Failed to parse arguments: unrecognised option '-9'

        // This doesn't respect the port defined above... does it need to?!
        $expected = $this->extractFromCli(
            "print_block $height",
            '/\nhash: (.*)\n/'
        );

        $result = $daemonRpc->onGetBlockHash($height);

        self::assertEquals($expected, $result);
    }
}
