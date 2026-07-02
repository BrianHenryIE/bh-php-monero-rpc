<?php

/**
 * Abstract class to perform JSON RPC calls and cast the response to string|stdClass|provided mapper.
 */

namespace BrianHenryIE\MoneroRpc;

use Exception;
use JsonMapper\Enums\TextNotation;
use JsonMapper\JsonMapperFactory;
use JsonMapper\Middleware\CaseConversion;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use SimPod\JsonRpc\Extractor\ResponseExtractor;
use SimPod\JsonRpc\HttpJsonRpcRequestFactory;
use stdClass;

abstract class RpcClient
{
    protected string $urlBase;

    /**
     * PSR HTTP implementation.
     */
    protected RequestFactoryInterface $requestFactory;

    /**
     * PSR HTTP client for making requests.
     */
    protected ClientInterface $client;

    protected UriFactoryInterface $uriFactory;
    protected StreamFactoryInterface $streamFactory;

    public const PORT = 18081;
    public const TESTNET_PORT = 28081;
    public const STAGENET_PORT = 38081;

    /**
     * Start a connection with the Monero daemon (monerod)
     *
     * @param UriFactoryInterface $uriFactory
     * @param RequestFactoryInterface $requestFactory
     * @param ClientInterface $client A PSR HTTP client.
     * @param string $host Monero daemon IP hostname
     * @param int $port Monero daemon port
     * @param bool $ssl Monero daemon protocol (i.e. use 'https' or just 'http')
     */
    public function __construct(
        UriFactoryInterface $uriFactory,
        RequestFactoryInterface $requestFactory,
        ClientInterface $client,
        StreamFactoryInterface $streamFactory,
        string $host = '127.0.0.1',
        int $port = self::PORT,
        bool $ssl = true,
    ) {
        $this->streamFactory  = $streamFactory;
        $this->client         = $client;
        $this->requestFactory = $requestFactory;
        $this->uriFactory     = $uriFactory;
        $this->urlBase        = sprintf(
            'http%s://%s:%d/',
            $ssl ? 's' : '',
            $host,
            $port
        );
    }

    /**
     * @param  ?string $username Monero daemon RPC username
     * @param  ?string $password Monero daemon RPC passphrase
     */
    public function setAuthorizationCredentials(string $username, string $password): void
    {
        // TODO
    }

    protected function runRpc(string $path, ?array $params = null, string $type = stdClass::class)
    {
        return $this->run($path, null, $params, $type);
    }

    protected function runJsonRpc(?string $method, ?array $params = null, ?string $type = stdClass::class)
    {

        return $this->run('json_rpc', $method, $params, $type);
    }

    /**
     * Execute RPC command.
     *
     * @template T of object
     *
     * @param string $path Path of API (by default "json_rpc").
     * @param ?string $method RPC method to call.
     * @param ?array<string,mixed> $params Parameters to pass.
     * @param ?class-string<T> $type The object type to cast/deserialize the response to, or null to return a string.
     *
     * @return T|String|array|void
     * @throws ClientExceptionInterface|RequestExceptionInterface
     */
    protected function run(string $path, ?string $method, ?array $params = null, ?string $type = stdClass::class)
    {
        $rpcRequestFactory = new HttpJsonRpcRequestFactory($this->requestFactory, $this->streamFactory);

        $id      = null;
        // Strip only null (i.e. unset optional) parameters. A plain `array_filter()`
        // here would also remove legitimate falsy values such as `0` (e.g. the
        // genesis block height in `on_getblockhash`), `false`, and `''`.
        $params  = array_filter(
            $params ?? [],
            function ($value) {
                return !is_null($value);
            }
        );
        $uri = $this->uriFactory->createUri($this->urlBase . $path);

        if ($path === 'json_rpc') {
            $request = $rpcRequestFactory->request($id, $method ?? '', $params);
            $request = $request->withUri($uri);
        } else {
            // "Other" daemon RPC endpoints (e.g. /get_transactions, /get_limit)
            // expect the parameters as the top-level JSON body, NOT wrapped in
            // a JSON-RPC envelope (which monerod silently ignores).
            $request = $this->requestFactory->createRequest('POST', $uri)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($this->streamFactory->createStream((string) json_encode((object) $params)));
        }

        // TODO: Credentials.

        // Throws RequestExceptionInterface.
        $response = $this->client->sendRequest($request);
        $extracted = new ResponseExtractor($response);

        if ($extracted->getErrorCode()) {
            // TODO:
	        // Method not found
            // Exception: Internal error: can't get block by hash. Hash = 41aea45eb8e6f627f3d9980de9f2048116bec00b4bd15b669d484681e881f6ef.
            // start_mining: Couldn't start mining due to unknown error.
            // rescan_blockchain: no connection to daemon
            // open_wallet: Failed to open wallet
	        // Exception: Couldn't start mining due to unknown error.
	        // Regtest required when generating blocks
	        // failed to get blocks
	        // Failed to parse wallet address
            throw new Exception($extracted->getErrorMessage());
        }

        $data = $path === 'json_rpc' ? json_encode($extracted->getResult()) : (string) $response->getBody();

        if (is_null($type)) {
            return trim($data, '"');
        }

        // Some methods we know return an empty array every time, indicating void.
        // Others maybe return an empty array only sometimes.
        if (($data === '[]')) {
            return [];
        }

        $mapper = ( new JsonMapperFactory() )->bestFit();

        $mapper->push(new CaseConversion(TextNotation::UNDERSCORE(), TextNotation::CAMEL_CASE()));

		// status: Failed, wrong address
        return $mapper->mapToClassFromString($data, $type);
    }
}
