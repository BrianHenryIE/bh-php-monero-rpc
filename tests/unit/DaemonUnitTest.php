<?php

namespace BrianHenryIE\MoneroRpc;

use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use PsrMock\Psr17\RequestFactory;
use PsrMock\Psr17\StreamFactory;
use PsrMock\Psr18\Client;
use PsrMock\Psr7\Response;
use PsrMock\Psr7\Uri;

/**
 * @coversDefaultClass \BrianHenryIE\MoneroRpc\Daemon
 */
class DaemonUnitTest extends TestCase
{
    private function getDaemonClient(string $path, string $responseBody): Daemon
    {
        $client = new Client();

        $uri = new Uri("https://127.0.0.1:18081/$path");

        $uriFactory = Mockery::mock(UriFactoryInterface::class);
        $uriFactory->shouldReceive('createUri')->andReturn($uri);

        $daemonRpcClient = new Daemon(
            $uriFactory,
            new RequestFactory(),
            $client,
            new StreamFactory()
        );

        $streamFactory = new StreamFactory();
        $responseStream = $streamFactory->createStream($responseBody);

        $response = (new Response())->withBody($responseStream);

        $client->addResponse(
            'POST',
            "https://127.0.0.1:18081/$path",
            $response
        );

        return $daemonRpcClient;
    }
    public function testMiningStatus(): void
    {
        $responseBody = <<<'EOD'
{
  "active": false,
  "address": "",
  "bg_idle_threshold": 0,
  "bg_ignore_battery": false,
  "bg_min_idle_seconds": 0,
  "bg_target": 0,
  "block_reward": 0,
  "block_target": 60,
  "difficulty": 13616,
  "difficulty_top64": 0,
  "is_background_mining_enabled": false,
  "pow_algorithm": "Cryptonight",
  "speed": 0,
  "status": "OK",
  "threads_count": 0,
  "untrusted": false,
  "wide_difficulty": "0x3530"
}
EOD;

        $daemonRpcClient = $this->getDaemonClient('mining_status', $responseBody);

        $result = $daemonRpcClient->miningStatus();

        self::assertFalse($result->active);
    }

    /**
     * Regression net for the float trap, end-to-end through the client: a block reward
     * above PHP's signed int64 max (here uint64 max, 18446744073709551615) must survive
     * `RpcClient::run()`'s JSON_BIGINT_AS_STRING decode and hydrate into an exact
     * MoneroAmount rather than a lossy float.
     *
     * @covers \BrianHenryIE\MoneroRpc\RpcClient::run
     */
    public function testBigIntegerRewardPreservedExactly(): void
    {
        $responseBody = <<<'EOD'
{
  "id": 0,
  "jsonrpc": "2.0",
  "result": {
    "blob": "00",
    "block_header": {
      "block_size": 1, "block_weight": 1, "cumulative_difficulty": 1,
      "cumulative_difficulty_top64": 0, "depth": 0, "difficulty": 1, "difficulty_top64": 0,
      "hash": "h", "height": 1, "long_term_weight": 1, "major_version": 16, "miner_tx_hash": "m",
      "minor_version": 16, "nonce": 0, "num_txes": 0, "orphan_status": false, "pow_hash": "",
      "prev_hash": "p", "reward": 18446744073709551615, "timestamp": 1700000000,
      "wide_cumulative_difficulty": "0x1", "wide_difficulty": "0x1"
    },
    "credits": 0, "json": "{}", "miner_tx_hash": "m", "top_hash": "",
    "status": "OK", "untrusted": false
  }
}
EOD;

        $daemonRpcClient = $this->getDaemonClient('json_rpc', $responseBody);

        $result = $daemonRpcClient->getBlockByHash('any');

        self::assertSame('18446744073709551615', $result->blockHeader->reward->toAtomicUnitsString());
    }

    public function testGetBlockCount(): void
    {
        $responseBody = <<<'EOD'
{
  "id": 0,
  "jsonrpc": "2.0",
  "result": {
    "count": 587251,
    "status": "OK",
    "untrusted": false
  }
}
EOD;

        $daemonRpcClient = $this->getDaemonClient('json_rpc', $responseBody);

        $result = $daemonRpcClient->getBlockCount();

        self::assertEquals(587251, $result->count);
    }
    public function testOnGetBlockHash(): void
    {
        $responseBody = <<<'EOD'
{
  "id": 0,
  "jsonrpc": "2.0",
  "result": "ad2f73a85939793f3dca7c470d5ee2618eb5d1e1c22b39dee83291978bf1ddb5"
}
EOD;

        $daemonRpcClient = $this->getDaemonClient('onGetBlockHash', $responseBody);

        $result = $daemonRpcClient->onGetBlockHash(12345);

        self::assertEquals('ad2f73a85939793f3dca7c470d5ee2618eb5d1e1c22b39dee83291978bf1ddb5', $result);
    }
}
