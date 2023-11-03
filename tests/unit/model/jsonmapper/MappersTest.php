<?php

namespace BrianHenryIE\MoneroExplorer\Model\JsonMapper;

use BrianHenryIE\MoneroRpc\Daemon\JsonMapper\AltBlocksHashesMapper;
use BrianHenryIE\MoneroRpc\Daemon\JsonMapper\BlockCountMapper;
use BrianHenryIE\MoneroRpc\Daemon\JsonMapper\BlockHeaderByMapper;
use BrianHenryIE\MoneroRpc\Daemon\JsonMapper\BlockMapper;
use BrianHenryIE\MoneroRpc\Daemon\JsonMapper\InfoMapper;
use BrianHenryIE\MoneroRpc\Daemon\JsonMapper\InPeersMapper;
use BrianHenryIE\MoneroRpc\Daemon\JsonMapper\LimitMapper;
use BrianHenryIE\MoneroRpc\Daemon\JsonMapper\MiningStatusMapper;
use BrianHenryIE\MoneroRpc\Daemon\JsonMapper\PeerListMapper;
use BrianHenryIE\MoneroRpc\Daemon\JsonMapper\ResponseBaseMapper;
use BrianHenryIE\MoneroRpc\Daemon\JsonMapper\TransactionPoolStatsMapper;
use JsonMapper\JsonMapperFactory;

class MappersTest extends \PHPUnit\Framework\TestCase
{
    /**
     *
     * @return array<string, string[]>
     */
    public static function data(): array
    {
        return [
            'get_alt_blocks_hashes.json' => [ 'get_alt_blocks_hashes.json', AltBlocksHashesMapper::class ],
//          'json_rpc-get_bans.json', => ['json_rpc-get_bans.json', BansMapper::class ],
            'json_rpc-getblockheaderbyheight.json' => ['json_rpc-getblockheaderbyheight.json', BlockHeaderByMapper::class],
            'stop_daemon.json' => ['stop_daemon.json', ResponseBaseMapper::class],
            'get_limit.json' => ['get_limit.json', LimitMapper::class],
            'json_rpc-get_info.json' => ['json_rpc-get_info.json', InfoMapper::class],
            'json_rpc-getlastblockheader.json' => ['json_rpc-getlastblockheader.json', BlockHeaderByMapper::class],
            'stop_mining.json' => ['stop_mining.json', ResponseBaseMapper::class],
            'get_peer_list.json' => ['get_peer_list.json', PeerListMapper::class ],
            'json_rpc-getblock.json' => ['json_rpc-getblock.json', BlockMapper::class],
            'mining_status.json' => ['mining_status.json',MiningStatusMapper::class],
            'get_transaction_pool_stats.json' => ['get_transaction_pool_stats.json',TransactionPoolStatsMapper::class],
            'json_rpc-getblockcount.json' => ['json_rpc-getblockcount.json',BlockCountMapper::class],
//          'save_bc.json'=>['save_bc.json',SaveBCMapper::class],
            'in_peers.json' => ['in_peers.json',InPeersMapper::class],
            'json_rpc-getblockheaderbyhash.json' => ['json_rpc-getblockheaderbyhash.json',BlockHeaderByMapper::class],
            'set_limit.json' => ['set_limit.json',LimitMapper::class],
        ];
    }

    /**
     * @dataProvider data
     *
     * @template T of object
     * @param string $filename The test .json file.
     * @param class-string<T> $type The object type to cast/deserialize the response to.
     */
    public function testMappers($filename, $type): void
    {
        try {
            $json = file_get_contents(__DIR__ . '/../../../_data/daemon/' . $filename);

            $mapper = (new JsonMapperFactory())->bestFit();

            $mapper->push(new \JsonMapper\Middleware\CaseConversion(
                \JsonMapper\Enums\TextNotation::UNDERSCORE(),
                \JsonMapper\Enums\TextNotation::CAMEL_CASE()
            ));

            $decoded_json = json_decode($json);
            if (is_array($decoded_json)) {
                $result = $mapper->mapToClassArray($decoded_json, $type);
            } else {
                $result = $mapper->mapToClass($decoded_json, $type);
            }
        } catch (\Throwable $throwable) {
            self::fail($filename . ' : ' . get_class($throwable) . ' - ' . $throwable->getMessage());
        }

        self::expectNotToPerformAssertions();
    }
}
