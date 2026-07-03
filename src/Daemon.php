<?php

/**
 *
 * @see https://www.getmonero.org/resources/developer-guides/daemon-rpc.html
 * @see https://monerodocs.org/interacting/monerod-reference/
 * @see https://github.com/monero-project/monero/wiki/Daemon-RPC-documentation
 *
 * @see https://github.com/monerodocs/md/blob/master/docs/interacting/monerod-reference.md
 * @see https://github.com/monero-project/monero/blob/master/src/daemon/command_server.cpp
 *
 * Note: "atomic units" refer to the smallest fraction of 1 XMR according to the monerod implementation.
 * 1 XMR = 1e12 atomic units.
 *
 * monerophp/daemonRPC
 *
 * A class for making calls to a Monero daemon's RPC API using PHP
 * https://github.com/monero-integrations/monerophp
 *
 * Using work from
 *   CryptoChangements [Monero_RPC] <bW9uZXJv@gmail.com> (https://github.com/cryptochangements34)
 *   Serhack [Monero Integrations] <nico@serhack.me> (https://serhack.me)
 *   TheKoziTwo [xmr-integration] <thekozitwo@gmail.com>
 *   Andrew LeCody [EasyBitcoin-PHP]
 *   Kacper Rowinski [jsonRPCClient] <krowinski@implix.com>
 *
 * @author     Monero Integrations Team <support@monerointegrations.com> (https://github.com/monero-integrations)
 * @copyright  2018
 * @license    MIT
 *
 * ============================================================================
 *
 * // See example.php for more examples
 *
 * // Initialize class
 * $daemonRPC = new daemonRPC();
 *
 * // Examples:
 * $height = $daemonRPC->getblockcount();
 * $block = $daemonRPC->getblock_by_height(1);
 *
 */

namespace BrianHenryIE\MoneroRpc;

use BrianHenryIE\MoneroRpc\Daemon\AltBlocksHashes;
use BrianHenryIE\MoneroRpc\Daemon\Block;
use BrianHenryIE\MoneroRpc\Daemon\BlockCount;
use BrianHenryIE\MoneroRpc\Daemon\BlockHeaderBy;
use BrianHenryIE\MoneroRpc\Daemon\BlockTemplate;
use BrianHenryIE\MoneroRpc\Daemon\Connection;
use BrianHenryIE\MoneroRpc\Daemon\Connections;
use BrianHenryIE\MoneroRpc\Daemon\GenerateBlocks;
use BrianHenryIE\MoneroRpc\Daemon\Height;
use BrianHenryIE\MoneroRpc\Daemon\Info;
use BrianHenryIE\MoneroRpc\Daemon\InPeers;
use BrianHenryIE\MoneroRpc\Daemon\Limit;
use BrianHenryIE\MoneroRpc\Daemon\MiningStatus;
use BrianHenryIE\MoneroRpc\Daemon\PeerList;
use BrianHenryIE\MoneroRpc\Daemon\ResponseBase;
use BrianHenryIE\MoneroRpc\Daemon\TransactionPoolStats;
use Exception;

class Daemon extends RpcClient
{
  /**
   * Look up how many blocks are in the longest chain known to the node.
   *
   * @see https://www.getmonero.org/resources/developer-guides/daemon-rpc.html#get_block_count
   * @see https://github.com/monero-project/monero/blob/e06129bb4d1076f4f2cebabddcee09f1e9e30dcc/src/rpc/core_rpc_server.cpp#L1687-L1701
   * @see https://github.com/monero-project/monero/blob/e06129bb4d1076f4f2cebabddcee09f1e9e30dcc/src/cryptonote_core/blockchain.cpp#L269-L277
   */
    public function getBlockCount(): BlockCount
    {
        return $this->runJsonRpc('get_block_count', null, BlockCount::class);
    }

  /**
   * Look up a block's hash by its height
   *
   * @param int $height Height of block to look up.
   *
   * @return string  Example: 'e22cf75f39ae720e8b71b3d120a5ac03f0db50bba6379e2850975b4859190bc6'
   *
   */
    public function onGetBlockHash($height): string
    {
        $params = array($height);

        // Also `on_get_block_hash`.
        return $this->runJsonRpc('on_getblockhash', $params, null);
    }

  /**
   * Construct a block template that can be mined upon
   *
   * @param  string  $walletAddress  Address of wallet to receive coinbase transactions if block is successfully mined
   * @param  int     $reserveSize    Reserve size
   *
   * https://monero.stackexchange.com/questions/6852/what-does-getblocktemplate-return
   *
   * Example: {
   *   "blocktemplate_blob": "01029af88cb70568b84a11dc9406ace9e635918ca03b008f7728b9726b327c1b482a98d81ed83000000000018bd03c01ffcfcf3c0493d7cec7020278dfc296544f139394e5e045fcda1ba2cca5b69b39c9ddc90b7e0de859fdebdc80e8eda1ba01029c5d518ce3cc4de26364059eadc8220a3f52edabdaf025a9bff4eec8b6b50e3d8080dd9da417021e642d07a8c33fbe497054cfea9c760ab4068d31532ff0fbb543a7856a9b78ee80c0f9decfae01023ef3a7182cb0c260732e7828606052a0645d3686d7a03ce3da091dbb2b75e5955f01ad2af83bce0d823bf3dbbed01ab219250eb36098c62cbb6aa2976936848bae53023c00000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000001f12d7c87346d6b84e17680082d9b4a1d84e36dd01bd2c7f3b3893478a8d88fb3",
   *   "difficulty": 982540729,
   *   "height": 993231,
   *   "prev_hash": "68b84a11dc9406ace9e635918ca03b008f7728b9726b327c1b482a98d81ed830",
   *   "reserved_offset": 246,
   *   "status": "OK"
   * }
   *
   */
    public function getBlockTemplate($walletAddress, $reserveSize): BlockTemplate
    {
        $params = array( 'wallet_address' => $walletAddress, 'reserve_size' => $reserveSize);

        return $this->runJsonRpc('getblocktemplate', $params, BlockTemplate::class);
    }

  /**
   * Submit a mined block to the network
   *
   * @param  string  $block  Block blob
   *
   * @return // TODO: example
   *
   */
    public function submitBlock($block)
    {
        return $this->runJsonRpc('submitblock', $block);
    }


    /**
     * Generate Blocks
     *
     * Is this a function just for testing? `--regtest`
     * @see https://github.com/monero-project/monero/commit/34cb6b4b703f30be89388f4cdcc5d7b6780ee988
     *
     * @see https://www.getmonero.org/resources/developer-guides/daemon-rpc.html#generateblocks
     *
     * @param int $amountOfBlocks - unsigned int; number of blocks to be generated.
     * @param string $walletAddress- string; address to receive the coinbase reward.
     * @param string $previousBlock
     * @param int $startingNonce - unsigned int; Increased by miner until it finds a matching result that solves a block.
     */
    public function generateBlocks(
        int $amountOfBlocks,
        string $walletAddress,
        string $previousBlock,
        int $startingNonce
    ): GenerateBlocks {
        $params = [
            'amount_of_blocks' => $amountOfBlocks,
            'wallet_address' => $walletAddress,
            'prev_block' => $previousBlock,
            'starting_nonce' => $startingNonce
        ];
        return $this->runJsonRpc('generateblocks', $params, GenerateBlocks::class);
    }

  /**
   * Look up the block header of the latest block in the longest chain known to the node
   *
   * Example: {
   *   "block_header": {
   *     "depth": 0,
   *     "difficulty": 746963928,
   *     "hash": "ac0f1e226268d45c99a16202fdcb730d8f7b36ea5e5b4a565b1ba1a8fc252eb0",
   *     "height": 990793,
   *     "major_version": 1,
   *     "minor_version": 1,
   *     "nonce": 1550,
   *     "orphan_status": false,
   *     "prev_hash": "386575e3b0b004ed8d458dbd31bff0fe37b280339937f971e06df33f8589b75c",
   *     "reward": 6856609225169,
   *     "timestamp": 1457589942
   *   },
   *   "status": "OK"
   * }
   *
   */
    public function getLastBlockHeader(): BlockHeaderBy
    {
        return $this->runJsonRpc('getlastblockheader', null, BlockHeaderBy::class);
    }

  /**
   * Look up a block header from a block hash
   *
   * @param  string  $hash  The block's SHA256 hash
   *
   * Example: {
   *   "block_header": {
   *     "depth": 78376,
   *     "difficulty": 815625611,
   *     "hash": "e22cf75f39ae720e8b71b3d120a5ac03f0db50bba6379e2850975b4859190bc6",
   *     "height": 912345,
   *     "major_version": 1,
   *     "minor_version": 2,
   *     "nonce": 1646,
   *     "orphan_status": false,
   *     "prev_hash": "b61c58b2e0be53fad5ef9d9731a55e8a81d972b8d90ed07c04fd37ca6403ff78",
   *     "reward": 7388968946286,
   *     "timestamp": 1452793716
   *   },
   *   "status": "OK"
   * }
   *
   */
    public function getBlockHeaderByHash($hash): BlockHeaderBy
    {
        $params = array('hash' => $hash);

        return $this->runJsonRpc('getblockheaderbyhash', $params, BlockHeaderBy::class);
    }

  /**
   * Look up a block header by height
   *
   * @param  int     $height  Height of block
   *
   * Example: {
   *   "block_header": {
   *     "depth": 78376,
   *     "difficulty": 815625611,
   *     "hash": "e22cf75f39ae720e8b71b3d120a5ac03f0db50bba6379e2850975b4859190bc6",
   *     "height": 912345,
   *     "major_version": 1,
   *     "minor_version": 2,
   *     "nonce": 1646,
   *     "orphan_status": false,
   *     "prev_hash": "b61c58b2e0be53fad5ef9d9731a55e8a81d972b8d90ed07c04fd37ca6403ff78",
   *     "reward": 7388968946286,
   *     "timestamp": 1452793716
   *   },
   *   "status": "OK"
   * }
   *
   */
    public function getBlockHeaderByHeight($height): BlockHeaderBy
    {
        $params = array('height' => $height);

        return $this->runJsonRpc('getblockheaderbyheight', $params, BlockHeaderBy::class);
    }

  /**
   * Look up block information by SHA256 hash
   *
   * @param  string  $hash  SHA256 hash of block
   *
   * Example: {
   *   "blob": "...",
   *   "block_header": {
   *     "depth": 12,
   *     "difficulty": 964985344,
   *     "hash": "510ee3c4e14330a7b96e883c323a60ebd1b5556ac1262d0bc03c24a3b785516f",
   *     "height": 993056,
   *     "major_version": 1,
   *     "minor_version": 2,
   *     "nonce": 2036,
   *     "orphan_status": false,
   *     "prev_hash": "0ea4af6547c05c965afc8df6d31509ff3105dc7ae6b10172521d77e09711fd6d",
   *     "reward": 6932043647005,
   *     "timestamp": 1457720227
   *   },
   *   "json": "...",
   *   "status": "OK"
   * }
   *
   */
    public function getBlockByHash($hash)
    {
        $params = array('hash' => $hash);

        return $this->runJsonRpc('getblock', $params);
    }

  /**
   * Look up block information by height
   *
   * @param  int     $height  Height of block
   *
   * Example: {
   *   "blob": "...",
   *   "block_header": {
   *     "depth": 80694,
   *     "difficulty": 815625611,
   *     "hash": "e22cf75f39ae720e8b71b3d120a5ac03f0db50bba6379e2850975b4859190bc6",
   *     "height": 912345,
   *     "major_version": 1,
   *     "minor_version": 2,
   *     "nonce": 1646,
   *     "orphan_status": false,
   *     "prev_hash": "b61c58b2e0be53fad5ef9d9731a55e8a81d972b8d90ed07c04fd37ca6403ff78",
   *     "reward": 7388968946286,
   *     "timestamp": 1452793716
   *   },
   *   "json": "...",
   *   "status": "OK"
   * }
   *
   */
    public function getBlockByHeight($height): Block
    {
        $params = array('height' => $height);

        return $this->runJsonRpc('getblock', $params, Block::class);
    }

  /**
   * Look up incoming and outgoing connections to your node
   *
   * Example: {
   *   "connections": [{
   *     "avg_download": 0,
   *     "avg_upload": 0,
   *     "current_download": 0,
   *     "current_upload": 0,
   *     "incoming": false,
   *     "ip": "76.173.170.133",
   *     "live_time": 1865,
   *     "local_ip": false,
   *     "localhost": false,
   *     "peer_id": "3bfe29d6b1aa7c4c",
   *     "port": "18080",
   *     "recv_count": 116396,
   *     "recv_idle_time": 23,
   *     "send_count": 176893,
   *     "send_idle_time": 1457726610,
   *     "state": "state_normal"
   *   },{
   *   ..
   *   }],
   *   "status": "OK"
   * }
   *
   * @see Connection
   */
    public function getConnections(): Connections
    {
        return $this->runJsonRpc('get_connections', null, Connections::class);
    }

  /**
   * Look up general information about the state of your node and the network
   *
   * Example: {
   *   "alt_blocks_count": 5,
   *   "difficulty": 972165250,
   *   "grey_peerlist_size": 2280,
   *   "height": 993145,
   *   "incoming_connections_count": 0,
   *   "outgoing_connections_count": 8,
   *   "status": "OK",
   *   "target": 60,
   *   "target_height": 993137,
   *   "testnet": false,
   *   "top_block_hash": "",
   *   "tx_count": 564287,
   *   "tx_pool_size": 45,
   *   "white_peerlist_size": 529
   * }
   *
   */
    public function getInfo(): Info
    {
        return $this->runJsonRpc('get_info', null, Info::class);
    }

  /**
   * Look up information regarding hard fork voting and readiness
   *
   *
   * Example: {
   *   "alt_blocks_count": 0,
   *   "block_size_limit": 600000,
   *   "block_size_median": 85,
   *   "bootstrap_daemon_address": ?,
   *   "cumulative_difficulty": 40859323048,
   *   "difficulty": 57406,
   *   "free_space": 888592449536,
   *   "grey_peerlist_size": 526,
   *   "height": 1066107,
   *   "height_without_bootstrap": 1066107,
   *   "incoming_connections_count": 1,
   *   "offline":  ?,
   *   "outgoing_connections_count": 1,
   *   "rpc_connections_count": 1,
   *   "start_time": 1519963719,
   *   "status": OK,
   *   "target": 120,
   *   "target_height": 1066073,
   *   "testnet": 1,
   *   "top_block_hash": e438aae56de8e5e5c8e0d230167fcb58bc8dde09e369ff7689a4af146040a20e,
   *   "tx_count": 52632,
   *   "tx_pool_size": 0,
   *   "untrusted": ?,
   *   "was_bootstrap_ever_used: ?,
   *   "white_peerlist_size": 5
   * }
   *
   */
    public function getHardForkInfo()
    {
        return $this->runJsonRpc('hard_fork_info');
    }

  /**
   * Ban another node by IP
   *
   * @param  array  $bans  Array of IP addresses to ban
   *
   * Example: {
   *   "status": "OK"
   * }
   *
   */
    public function setBans($bans)
    {
        if (is_string($bans)) {
            $bans = array($bans);
        }
        $params = array('bans' => $bans);

        return $this->runJsonRpc('set_bans', $params);
    }

  /**
   * Get list of banned IPs
   *
   * Example: {
   *   "bans": [{
   *     "ip": 838969536,
   *     "seconds": 1457748792
   *   }],
   *   "status": "OK"
   * }
   *
   */
    public function getBans(): ResponseBase
    {
        return $this->runJsonRpc('get_bans', null, ResponseBase::class);
    }

  /**
   * Flush Transaction Pool
   *
   * @param $txids - array
   * Optional, list of transactions IDs to flush from pool (all tx ids flushed if empty).
   *
   * @return array status - string; General RPC error code. "OK" means everything looks good.
   */
    public function flushTxPool($txids)
    {
        return $this->runJsonRpc('flush_txpool', $txids);
    }

    /**
     * Get height
     *
     */
    public function getHeight(): Height
    {
        return $this->runRpc('getheight', null, Height::class);
    }

   /**
    * Get transactions
    *
    * TODO: obviously incomplete.
    */
    public function getTransactions(?array $txsHashes = null)
    {
        $params = array( 'txs_hashes' => $txsHashes, 'decode_as_json' => true);
        return $this->runRpc('get_transactions', $params);
    }


    public function getAltBlocksHashes(): AltBlocksHashes
    {
        return $this->runRpc('get_alt_blocks_hashes', null, AltBlocksHashes::class);
    }

    public function isKeyImageSpent($keyImages)
    {
        if (is_string($keyImages)) {
            $keyImages = array($keyImages);
        }
        if (!is_array($keyImages)) {
            throw new Exception('Error: key images must be an array or a string');
        }
        $params = array('key_images' => $keyImages);
        return $this->runRpc('is_key_image_spent', $params);
    }

    public function sendRawTransaction($txAsHex, $doNotRelay = false, $doSanityChecks = true)
    {
        $params = array( 'tx_as_hex' => $txAsHex, 'do_not_relay' => $doNotRelay, 'do_sanity_checks' => $doSanityChecks);
        return $this->runRpc('send_raw_transaction', $params);
    }

    /**
     * @see Wallet::startMining()
     */
    public function startMining(bool $backgroundMining, bool $ignoreBattery, string $minerAddress, int $threadsCount = 1)
    {
        if ($threadsCount < 0) {
            trigger_error('Error: threads_count must be a positive integer', E_USER_WARNING);
            $threadsCount = 1;
        }
        $params = array(
            'do_background_mining' => $backgroundMining,
            'ignore_battery' => $ignoreBattery,
            'miner_address' => $minerAddress,
            'threads_count' => $threadsCount
        );
        return $this->runRpc('start_mining', $params);
    }

    public function stopMining(): ResponseBase
    {
        return $this->runRpc('stop_mining', null, ResponseBase::class);
    }

    public function miningStatus(): MiningStatus
    {
        return $this->runRpc('mining_status', null, MiningStatus::class);
    }

    public function saveBc(): ResponseBase
    {
        return $this->runRpc('save_bc', null, ResponseBase::class);
    }

    public function getPeerList(bool $publicOnly = true): PeerList
    {
        $params = array('public_only' => $publicOnly);
        return $this->runRpc('get_peer_list', $params, PeerList::class);
    }

    /**
     * Set the log hash rate display mode.
     *
     * Control whether the hash rate is of the mining hardware is logged.
     *
     * @see https://www.getmonero.org/resources/developer-guides/daemon-rpc.html#set_log_hash_rate
     *
     * @param bool $visible
     */
    public function setLogHashRate(bool $visible = true): ResponseBase
    {
        $params = array('visible' => $visible); // TODO: does this need the bool as a string? int?
        return $this->runRpc('set_log_hash_rate', $params, ResponseBase::class);
    }

    public function setLogLevel(int $logLevel = 0)
    {
        if (!is_int($logLevel)) {
            throw new Exception('Error: log_level must be an integer');
        }
        $params = array('level' => $logLevel);
        return $this->runRpc('set_log_level', $params);
    }

    public function setLogCategories($category)
    {
        $params = array('categories' => $category);
        return $this->runRpc('set_log_categories', $params);
    }

    public function getTransactionPool()
    {
        return $this->runRpc('get_transaction_pool');
    }

    public function getTransactionPoolStats(): TransactionPoolStats
    {
        return $this->runRpc('get_transaction_pool_stats', null, TransactionPoolStats::class);
    }

    public function stopDaemon(): ResponseBase
    {
        return $this->runRpc('stop_daemon', null, ResponseBase::class);
    }

    public function getLimit(): Limit
    {
        return $this->runRpc('get_limit', null, Limit::class);
    }

    public function setLimit(int $limitDown, int $limitUp): Limit
    {
        $params = array( 'limit_down' => $limitDown, 'limit_up' => $limitUp);
        return $this->runRpc('set_limit', $params, Limit::class);
    }

    public function outPeers()
    {
        return $this->runRpc('out_peers');
    }

    public function inPeers(): InPeers
    {
        return $this->runRpc('in_peers', null, InPeers::class);
    }

    public function startSaveGraph()
    {
        return $this->runRpc('start_save_graph');
    }

    public function stopSaveGraph()
    {
        return $this->runRpc('stop_save_graph');
    }

    public function getOuts($outputs)
    {
        $params = array('outputs' => $outputs);
        return $this->runRpc('get_outs', $params);
    }
}
