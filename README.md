[![PHP 8.4](https://img.shields.io/badge/PHP-8.4-8892BF.svg)]() [![PHPCS PSR-12](https://img.shields.io/badge/PHPCS-PSR–12❌-lightgrey.svg)](https://www.php-fig.org/psr/psr-12/) [![PHPUnit ](.github/coverage.svg)](https://brianhenryie.github.io/bh-php-monero-rpc/) [![PHPStan ](.github/phpstan.svg)](https://phpstan.org/)

# Monero RPC PHP Client

> ⚠️ Work in progress. 

Goal is to return strongly typed objects from the RPC response. Ultimately to use in the [monero-integrations/monerowp WooCommerce plugin](https://github.com/monero-integrations/monerowp).

This project is `daemonRPC.php` and `walletRPC.php` extracted from [monero-integrations/monerophp](https://github.com/monero-integrations/monerophp).

Status: Much of `Daemon` is strongly typed and unit tested. `Wallet` returns `stdClass` objects.

Before v1.0, function signatures are expected to change as they are properly documented.

## Install

Requires PHP 8.4 or newer.

```bash
composer config minimum-stability dev
composer config prefer-stable true

composer config repositories.brianhenryie/bh-php-monero-rpc git https://github.com/brianhenryie/bh-php-monero-rpc

composer require --fixed brianhenryie/bh-php-monero-rpc
```

## Operation

Start the Monero daemon (`monerod`).

```bash
monerod --detach
```

```php
// Guzzle HttpFactory implements RequestFactoryInterface, UriFactoryInterface, and StreamFactoryInterface.
$httpFactory = new \GuzzleHttp\Psr7\HttpFactory();
/** @var Psr\Http\Message\UriFactoryInterface $uriFactory */
$uriFactory = $httpFactory;
/** @var Psr\Http\Message\RequestFactoryInterface $requestFactory */
$requestFactory = $httpFactory;
/** @var Psr\Http\Client\ClientInterface $client */
$client = new \GuzzleHttp\Client();
/** @var Psr\Http\Message\StreamFactoryInterface $streamFactory */
$streamFactory = $httpFactory;

$monero = new \BrianHenryIE\MoneroRpc\Daemon(
    $uriFactory,
    $requestFactory,
    $client,
    $streamFactory,
);

$result = $monero->getBlockCount()->count;
```

### Amounts, timestamps, and enums

Currency amounts are `MoneroAmount` value objects wrapping a `brick/math` `BigInteger` of atomic
units (1 XMR = 10¹²). A plain PHP `int` is *signed* 64-bit and cannot represent monerod's full
`uint64` range — and `json_decode()` silently degrades an out-of-range integer to a lossy `float` —
so `int` for atomic units is a real precision bug, not a style choice.

```php
$wallet  = new \BrianHenryIE\MoneroRpc\Wallet($uriFactory, $requestFactory, $client, $streamFactory);
$balance = $wallet->getBalance();

echo $balance->unlockedBalance->toXmr();               // "1.23"
echo $balance->unlockedBalance->toAtomicUnitsString(); // "1230000000000"

// Send 1.23 XMR. transfer() takes a MoneroAmount (atomic units) and a typed priority enum.
$wallet->transfer(
    \BrianHenryIE\MoneroRpc\MoneroAmount::fromXmr('1.23'),
    $recipientAddress,
    priority: \BrianHenryIE\MoneroRpc\Wallet\TransferPriority::Normal,
);
```

True epoch timestamps (e.g. `BlockHeader::$timestamp`, `Info::$startTime`) are `DateTimeImmutable`
in UTC (`0` → `null`); durations in seconds and `unlock_time` stay `int`. Closed monerod value sets
(`nettype`, connection `state`, key type, priority, …) are string/int-backed enums.

## Contributing 

> ⚠️ PRs helping improve the PhpDoc of methods are very welcome.

There are three PHPUnit test suites (see `phpunit.xml`):

* `unit` – no network access; mocks RPC responses. `tests/unit/model/jsonmapper` uses JSON saved in `tests/_data` to test the model parsing. Runs by default with `composer test`.
* `integration` – read-only tests against a live, deterministic, Dockerised Monero regtest stack (two peered `monerod` daemons and two `monero-wallet-rpc` servers; see `docker-compose.yml`).
* `integration-mutating` – integration tests which mutate chain/wallet/daemon state; always run after `integration`.

The integration stack is seeded by `tests/integration/seed-monero-regtest-chain.php`: it restores two wallets from mnemonics committed in `tests/integration/MoneroRegtestFixture.php`, mines 120 blocks, transfers exactly 1.23 XMR between the wallets, and mines 10 more. Heights, addresses, and balances are therefore identical on every run and are asserted as constants; block hashes and txids depend on timestamps and differ per run, so the seed script writes them to `tests/_data/integration/manifest.json` (gitignored) for tests to read.

* Run the full integration cycle (clean slate → up → seed → test → down):

```bash
make integration
```

* Or step by step:

```bash
make integration-up      # docker compose up -d --wait
make integration-seed    # build the deterministic chain + manifest
composer test-integration
make integration-down    # docker compose down -v (destroys all state)
```

* Run the unit tests:

```bash
composer test
```

* Run PHP Code Beautifer, CodeSniffer, and PhpStan:

```bash
composer lint
```

To add a strongly typed response to a Daemon or Wallet method which does not have one: call the method in an integration test and copy the live JSON response (or temporarily log `$data` in `RpcClient::run()`). Save the `result` key in `tests/_data` with an appropriate name.

Write a single `final readonly class` (no interface, no mapper) whose constructor uses promoted public properties in `camelCase` — the `CaseConversion` middleware maps monerod's `snake_case` keys automatically, and the `Constructor` middleware hydrates the class through its constructor. Guidelines:

- **Fields are required by default** (no constructor default). A response missing a required field throws `IncompleteRpcResponseException` rather than fabricating a value that could flow into a payment decision. Only make a field optional — nullable `?T $x = null`, or `= []` for a list — when you have observed monerod actually omitting it, and record that evidence in the property's docblock. (Optional/defaulted parameters must come after the required ones.)
- **Document parameters in the constructor `@param` block, not as inline docblocks on the promoted parameters.** The hydrator throws if a promoted property carries a docblock without a `@var` tag, so put descriptions, `@see` links, and array element types (`@param Connection[] $connections`) on the constructor.
- Responses that carry monerod's `status`/`untrusted` extend `Daemon\ResponseBase` and forward both via `parent::__construct(...)`.
- **Type by domain, not by wire shape:** currency amounts are `MoneroAmount` (never `int` — see above); true epoch timestamps are `?DateTimeImmutable` (UTC, `0` → `null`); closed monerod value sets are string/int-backed enums (`Enum::from()` throws on an unknown value, which is the desired tripwire). Durations in seconds, hashrates, counts, difficulties, and `unlock_time` (a block height OR a timestamp — document the trap, don't type it) stay `int`. Amounts and epoch fields hydrate through the factories registered in `RpcClient::buildResponseMapper()`.

Add the new class to the `MappersTest.php` dataprovider (using the shared `RpcClient::buildResponseMapper()`), and add an integration test assertion against the live value.

## Documentation

* https://github.com/monero-project/monero/wiki/Daemon-RPC-documentation
* https://www.jsonrpc.org/specification
* https://en.wikipedia.org/wiki/JSON-RPC
* Documentation can be found in the [`/docs`](tree/master/docs) folder.


## Goals

* [ ] Strongly typed 
* [ ] Test coverage
* [ ] PhpDoc
* [x] PSR abstraction (~~currently has a nyholm/psr7 dependency that can [maybe be removed](https://github.com/simPod/PhpJsonRpc/issues/70)~~)
* [ ] Short tutorials

## Test Data

* [monerojs/monerojs](https://github.com/monerojs/monerojs/blob/dev/test/index_test.js)
* [monero-rs/monero-rpc-rs](https://github.com/monero-rs/monero-rpc-rs/blob/main/tests/clients_tests/basic_daemon_rpc.rs)
* [monero-ecosystem/python-monerorpc](https://github.com/monero-ecosystem/python-monerorpc/blob/master/examples/test_rpc_batch.py)
* [monero-ecosystem/monero-python](https://github.com/monero-ecosystem/monero-python/blob/master/tests/test_jsonrpcdaemon.py)
* [monero-ecosystem/monero-java](https://github.com/monero-ecosystem/monero-java/blob/master/src/test/java/test/TestMoneroDaemonRpc.java)

## Dependencies

* [simpod/json-rpc](https://github.com/simPod/PhpJsonRpc) – 46% coverage. PSR compliant
* [JsonMapper/JsonMapper](https://github.com/JsonMapper/JsonMapper) | [JsonMapper.net](https://jsonmapper.net) - 100% coverage

## Acknowledgements

* The Monero Integrations team. [monerointegrations.com](https://monerointegrations.com) | [github.com/monero-integrations](https://github.com/monero-integrations/monerophp/graphs/contributors)
* [jacobdekeizer json-to-php-generator](https://jacobdekeizer.github.io/json-to-php-generator/#/)


## Docker

`make integration-up` starts the Dockerised Monero regtest stack used by the integration tests (pinned via `MONERO_VERSION` in `.env`). `make integration-down` destroys it, including all chain and wallet state.


```
monero-wallet-cli --stagenet --use-english-language-names --generate-new-wallet wallet1 --create-address-file --password 'password' --daemon-port 48081
```

```
monero-wallet-cli --stagenet --daemon-address 127.0.0.1:38089
monero-wallet-cli --stagenet --daemon-port 48081
```