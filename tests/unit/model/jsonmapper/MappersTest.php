<?php

namespace BrianHenryIE\MoneroRpc\Model\JsonMapper;

use BrianHenryIE\MoneroRpc\Daemon\AltBlocksHashes;
use BrianHenryIE\MoneroRpc\Daemon\Block;
use BrianHenryIE\MoneroRpc\Daemon\BlockCount;
use BrianHenryIE\MoneroRpc\Daemon\BlockHeaderBy;
use BrianHenryIE\MoneroRpc\Daemon\Connections;
use BrianHenryIE\MoneroRpc\Daemon\Info;
use BrianHenryIE\MoneroRpc\Daemon\InPeers;
use BrianHenryIE\MoneroRpc\Daemon\Limit;
use BrianHenryIE\MoneroRpc\Daemon\MiningStatus;
use BrianHenryIE\MoneroRpc\Daemon\PeerList;
use BrianHenryIE\MoneroRpc\Daemon\ResponseBase;
use BrianHenryIE\MoneroRpc\Daemon\TransactionPoolStats;
use BrianHenryIE\MoneroRpc\RpcClient;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Regression net: every committed daemon fixture must hydrate into its readonly response model
 * through the single shared hydrator ({@see RpcClient::buildResponseMapper()}). Because the models'
 * fields are required (PHP84_READONLY_MODELS_PLAN.md design decision 5), a fixture missing a field
 * the model requires fails here — this is the intended tripwire against stale fixtures and
 * mis-typed models.
 */
class MappersTest extends \PHPUnit\Framework\TestCase
{
    /**
     *
     * @return array<string, array{0: string, 1: class-string}>
     */
    public static function data(): array
    {
        return [
            'get_alt_blocks_hashes.json' => [ 'get_alt_blocks_hashes.json', AltBlocksHashes::class ],
            'json_rpc-getblockheaderbyheight.json' => ['json_rpc-getblockheaderbyheight.json', BlockHeaderBy::class],
            'stop_daemon.json' => ['stop_daemon.json', ResponseBase::class],
            'get_limit.json' => ['get_limit.json', Limit::class],
            'json_rpc-get_info.json' => ['json_rpc-get_info.json', Info::class],
            'json_rpc-getlastblockheader.json' => ['json_rpc-getlastblockheader.json', BlockHeaderBy::class],
            'stop_mining.json' => ['stop_mining.json', ResponseBase::class],
            'get_peer_list.json' => ['get_peer_list.json', PeerList::class ],
            'json_rpc-getblock.json' => ['json_rpc-getblock.json', Block::class],
            'mining_status.json' => ['mining_status.json', MiningStatus::class],
            'get_transaction_pool_stats.json' => ['get_transaction_pool_stats.json', TransactionPoolStats::class],
            'json_rpc-getblockcount.json' => ['json_rpc-getblockcount.json', BlockCount::class],
            'in_peers.json' => ['in_peers.json', InPeers::class],
            'json_rpc-getblockheaderbyhash.json' => ['json_rpc-getblockheaderbyhash.json', BlockHeaderBy::class],
            'set_limit.json' => ['set_limit.json', Limit::class],
            'get_connections.json' => ['get_connections.json', Connections::class],
        ];
    }

    /**
     * @template T of object
     * @param string $filename The test .json file.
     * @param class-string<T> $type The object type to cast/deserialize the response to.
     */
    #[DataProvider('data')]
    public function testMappers(string $filename, string $type): void
    {
        $json = (string) file_get_contents(__DIR__ . '/../../../_data/daemon/' . $filename);

        // `json_rpc` responses wrap the payload in an envelope; the model maps the `result` (this
        // mirrors RpcClient::run()). "Other" endpoint bodies ARE the payload.
        $decoded = json_decode($json);
        $payload = (is_object($decoded) && property_exists($decoded, 'result')) ? $decoded->result : $decoded;

        $mapper = RpcClient::buildResponseMapper();

        try {
            if (is_array($payload)) {
                $result = $mapper->mapToClassArray($payload, $type);
            } else {
                $result = $mapper->mapToClass($payload, $type);
            }
        } catch (\Throwable $throwable) {
            self::fail($filename . ' : ' . get_class($throwable) . ' - ' . $throwable->getMessage());
        }

        self::assertInstanceOf($type, $result);
    }
}
