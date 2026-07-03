<?php

/**
 * Abstract class to perform JSON RPC calls and cast the response to string|stdClass|provided mapper.
 */

namespace BrianHenryIE\MoneroRpc;

use BrianHenryIE\MoneroRpc\Exception\IncompleteRpcResponseException;
use BrianHenryIE\MoneroRpc\Exception\MoneroRpcErrorException;
use BrianHenryIE\MoneroRpc\JsonMapperFactory\DateTimeImmutableFactory;
use BrianHenryIE\MoneroRpc\JsonMapperFactory\MoneroAmountFactory;
use DateTimeImmutable;
use JsonMapper\Enums\TextNotation;
use JsonMapper\Handler\FactoryRegistry;
use JsonMapper\Handler\PropertyMapper;
use JsonMapper\JsonMapperBuilder;
use JsonMapper\JsonMapperInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
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

        // Decode the raw body ONCE with JSON_BIGINT_AS_STRING. monerod amounts are
        // unsigned 64-bit atomic units and some fields (cumulative emission/difficulty)
        // exceed PHP's signed-int64 range; a plain json_decode() — as SimPod's
        // ResponseExtractor performs — silently degrades those to lossy floats. With this
        // flag they arrive as numeric strings instead. Re-encoding below then quotes them,
        // so the mapper's own decode reads them back as strings (which MoneroAmount's
        // factory accepts) and never sees an out-of-range bare number. See {@see MoneroAmount}.
        $decoded = json_decode(
            (string) $response->getBody(),
            false,
            512,
            JSON_BIGINT_AS_STRING | JSON_THROW_ON_ERROR
        );

        if (is_object($decoded) && isset($decoded->error)) {
            // monerod returns a JSON-RPC error object; surface it as a MoneroRpcErrorException
            // carrying the numeric code + message + optional data. Examples of messages seen:
            //   -6  "Wrong block blob"; -32601 "Method not found"; -48 "multisig is disabled";
            //   "Failed to open wallet"; "Failed to parse wallet address"; "no connection to daemon".
            throw new MoneroRpcErrorException(
                (string) ($decoded->error->message ?? 'Unknown JSON-RPC error'),
                (int) ($decoded->error->code ?? 0),
                $decoded->error->data ?? null
            );
        }

        // For the JSON-RPC envelope the payload is `result`; other daemon endpoints return
        // the payload as the top-level body. Either way, re-encode the bigint-safe decode
        // so the string carried downstream preserves large values exactly.
        $payload = ($path === 'json_rpc' && is_object($decoded)) ? ($decoded->result ?? null) : $decoded;
        $data = (string) json_encode($payload);

        if (is_null($type)) {
            return trim($data, '"');
        }

        // Some methods we know return an empty array every time, indicating void.
        // Others maybe return an empty array only sometimes.
        if (($data === '[]')) {
            return [];
        }

        // status: Failed, wrong address
        // The mapper silently zero-fills a missing scalar (absent int → 0), so strictness
        // (PHP84_READONLY_MODELS_PLAN.md decision 5) is enforced HERE, before mapping: a response
        // missing a required top-level field throws rather than fabricating a value that could flow
        // into a payment decision downstream.
        self::assertResponseComplete($method ?? $path, $type, $data);

        return self::buildResponseMapper()->mapToClassFromString($data, $type);
    }

    /**
     * Enforce that $data contains every field $type requires, throwing otherwise.
     *
     * Response models are `readonly` classes whose required fields have no constructor default
     * (design decision 5). The JsonMapper does NOT fail on a missing field — it zero-fills the
     * value — so completeness is verified here by reflecting $type's constructor and diffing its
     * required (non-optional, non-nullable) parameters against the response's top-level keys
     * (snake_case → camelCase, matching the mapper). Optional params (with a default, e.g. `= null`
     * or `= []`) and nullable params are skipped. Nested-object completeness is not deep-checked;
     * the safety-critical fields (balances, heights, keys) are top-level.
     *
     * @param class-string $type
     * @throws IncompleteRpcResponseException when a required field is absent.
     */
    public static function assertResponseComplete(string $rpcMethod, string $type, string $data): void
    {
        if (!class_exists($type)) {
            return;
        }
        $constructor = ( new \ReflectionClass($type) )->getConstructor();
        if ($constructor === null) {
            return;
        }

        $decoded = json_decode($data, true);
        if (!is_array($decoded)) {
            return;
        }
        /** @var array<string, mixed> $decoded */
        $presentKeys = array_keys($decoded);
        $presentCamel = array_map(
            static fn ($key): string => lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', (string) $key)))),
            $presentKeys
        );

        $missing = [];
        foreach ($constructor->getParameters() as $parameter) {
            if ($parameter->isOptional() || $parameter->allowsNull()) {
                continue;
            }
            if (!in_array($parameter->getName(), $presentCamel, true)) {
                $missing[] = $parameter->getName();
            }
        }

        if ($missing === []) {
            return;
        }

        $message = sprintf(
            'Incomplete RPC response for method "%s" mapping to %s: missing required %s [%s]. Response keys present: [%s].',
            $rpcMethod,
            $type,
            count($missing) === 1 ? 'property' : 'properties',
            implode(', ', $missing),
            implode(', ', array_map('strval', $presentKeys))
        );

        throw new IncompleteRpcResponseException($message, $data);
    }

    /**
     * The single construction site for the JsonMapper used to hydrate every response model.
     *
     * Response models are immutable `readonly` classes hydrated via constructor property
     * promotion, so the Constructor middleware (with a shared {@see FactoryRegistry}) is
     * required — it registers a factory that passes JSON values positionally to each class's
     * constructor rather than writing properties directly. `CaseConversion` maps monerod's
     * snake_case keys to the classes' camelCase parameters; nested-object and array element
     * types are resolved from the `@var`/`@param` docblocks on the promoted parameters.
     *
     * Both {@see RpcClient::run()} and the fixture-mapping tests build the mapper here so the
     * two can never diverge.
     *
     * The FactoryRegistry is built by hand rather than via {@see FactoryRegistry::withNativePhpClassesAdded()}
     * because that helper pre-registers a *string*-based `DateTimeImmutable` factory and
     * `addFactory()` throws on a duplicate class name — we need an epoch-*integer*
     * `DateTimeImmutable` factory instead ({@see DateTimeImmutableFactory}). We also register
     * {@see MoneroAmountFactory} for atomic-unit amounts. Enums need no factory: json-mapper
     * hydrates a BackedEnum property natively via `Enum::from()`, which throws on an unknown
     * value — exactly the closed-set behaviour we want.
     */
    public static function buildResponseMapper(): JsonMapperInterface
    {
        $factoryRegistry = ( new FactoryRegistry() )
            ->addFactory(stdClass::class, static fn ($value) => (object) $value)
            ->addFactory(DateTimeImmutable::class, new DateTimeImmutableFactory())
            ->addFactory(MoneroAmount::class, new MoneroAmountFactory());

        return ( new JsonMapperBuilder() )
            ->withPropertyMapper(new PropertyMapper($factoryRegistry))
            ->withDocBlockAnnotationsMiddleware()
            ->withTypedPropertiesMiddleware()
            ->withNamespaceResolverMiddleware()
            ->withObjectConstructorMiddleware($factoryRegistry)
            ->withCaseConversionMiddleware(TextNotation::UNDERSCORE(), TextNotation::CAMEL_CASE())
            ->build();
    }
}
