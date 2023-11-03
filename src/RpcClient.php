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
     * @return T|String
     * @throws ClientExceptionInterface
     */
    protected function run(string $path, ?string $method, ?array $params = null, ?string $type = stdClass::class)
    {
        $rpcRequestFactory = new HttpJsonRpcRequestFactory($this->requestFactory, $this->streamFactory);

        $id      = null;
        $request = $rpcRequestFactory->request($id, $method ?? '', $params);

        $uri     = $this->uriFactory->createUri($this->urlBase . $path);
        $request = $request->withUri($uri);

        // TODO: Credentials.

        $response  = $this->client->sendRequest($request);
        $extracted = new ResponseExtractor($response);

        $data = $path === 'json_rpc' ? json_encode($extracted->getResult()) : (string) $response->getBody();

        if ($extracted->getErrorCode()) {
            // TODO:
            throw new Exception($extracted->getErrorMessage());
        }

        if (is_null($type)) {
            return trim($data, '"');
        }

        $mapper = ( new JsonMapperFactory() )->bestFit();

        $mapper->push(new CaseConversion(TextNotation::UNDERSCORE(), TextNotation::CAMEL_CASE()));

        return $mapper->mapToClassFromString($data, $type);
    }
}
