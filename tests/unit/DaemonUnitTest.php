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
     * Regression net for the float trap: a JSON-RPC result value above PHP's signed
     * int64 max (here uint64 max, 18446744073709551615) must survive decoding exactly.
     * `RpcClient::run()` decodes with JSON_BIGINT_AS_STRING, so the value reaches an
     * untyped stdClass result as the exact numeric string rather than a lossy float.
     *
     * @covers \BrianHenryIE\MoneroRpc\RpcClient::run
     */
    public function testBigIntegerResultValuePreservedExactly(): void
    {
        $responseBody = <<<'EOD'
{
  "id": 0,
  "jsonrpc": "2.0",
  "result": {
    "reward": 18446744073709551615,
    "status": "OK",
    "untrusted": false
  }
}
EOD;

        $daemonRpcClient = $this->getDaemonClient('json_rpc', $responseBody);

        // getBlockByHash returns an untyped stdClass, so no scalar coercion occurs.
        $result = $daemonRpcClient->getBlockByHash('any');

        self::assertSame('18446744073709551615', $result->reward);
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
