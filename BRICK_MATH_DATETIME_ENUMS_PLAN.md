# Plan: brick/math for currency, DateTimeImmutable for timestamps, enums

Implementation plan for replacing primitive types in the response models and method
signatures of `brianhenryie/bh-php-monero-rpc` with value objects. Written to be
executed step-by-step by an AI coding agent. Each phase has acceptance criteria; do
not start a phase until the previous phase's criteria pass.

**Ordering:** implement AFTER PHP84_READONLY_MODELS_PLAN.md (this plan registers
factories at that plan's single hydrator construction site and edits the readonly
model classes it produces).

## Why (the concrete motivation, not just style)

monerod's amounts are **unsigned 64-bit integers** of atomic units (1 XMR = 1e12).
PHP's `int` is *signed* 64-bit (max ~9.22e18), and Monero's cumulative emission is
already ~1.84e19 atomic units — fields like `already_generated_coins` cannot be
represented by PHP `int` at all. Worse, `json_decode()` silently converts an
out-of-range integer to a lossy `float` unless decoded with `JSON_BIGINT_AS_STRING`.
So `int` for currency is not merely inelegant; it is a live precision bug for large
values. `brick/math` `BigInteger` (backed by the already-required ext-bcmath, or GMP
when available) represents the full range exactly.

## Design decisions (do not re-litigate during implementation)

1. **`MoneroAmount` value object, not raw `BigInteger`, not brick/money.** A single
   `final readonly class MoneroAmount` wraps `Brick\Math\BigInteger` atomic units.
   brick/money's currency dimension buys nothing here (one currency), and a domain
   type reads better than `BigInteger` in signatures and prevents accidental mixing
   of atomic-unit and XMR-denominated numbers. Minimal API:
   - `MoneroAmount::fromAtomicUnits(int|string|BigInteger $atomicUnits): self`
   - `MoneroAmount::fromXmr(string $xmrDecimal): self` — exact conversion via
     `BigDecimal`; THROWS on more than 12 decimal places (never rounds silently).
     This replaces and deletes `Wallet::_transform()`.
   - `$amount->atomicUnits: BigInteger` (public readonly property)
   - `toAtomicUnitsString(): string`, `toXmr(): BigDecimal`,
     `isEqualTo(self): bool`, `compareTo(self): int`, `plus(self): self`,
     `minus(self): self`, `isZero(): bool`, `__toString()` (atomic units).
2. **Timestamps become `DateTimeImmutable` (UTC) — but ONLY true epoch timestamps.**
   monerod mixes epoch timestamps, durations, and one dual-meaning field; converting
   the wrong ones would be worse than `int`:
   - CONVERT (epoch seconds → `DateTimeImmutable` in UTC): `BlockHeader::$timestamp`,
     `Info::$adjustedTime`, `Info::$startTime`,
     `TransactionPoolStatsStats::$oldest`.
   - `0` meaning "unset/none" (e.g. `oldest` with an empty pool) maps to `null`;
     such fields are typed `?DateTimeImmutable`. Document per field.
   - DO NOT CONVERT: durations in seconds stay `int`
     (`Connection::$liveTime`, `$recvIdleTime`, `$sendIdleTime`,
     `MiningStatus::$blockTarget`, `Info::$target`); hashrates stay `int`
     (`MiningStatus::$speed`); and **`unlock_time` stays a raw `int` wherever it
     appears** — Monero's `unlock_time` is a block HEIGHT when < 500000000 and an
     epoch TIMESTAMP otherwise; encode that trap in its docblock, not in a type.
3. **String-backed enums where the value set is closed; strings where it is open.**
   - `Daemon\NetType: string` — `Mainnet = 'mainnet'`, `Testnet = 'testnet'`,
     `Stagenet = 'stagenet'`, `Fakechain = 'fakechain'` (regtest reports
     `fakechain`; the integration suite already asserts this) — for
     `Info::$nettype`.
   - `Daemon\ConnectionState: string` — `state_before_handshake`,
     `state_synchronizing`, `state_standby`, `state_normal` (verify the full set in
     monerod's `connection_basic` serialization before finalizing) — for
     `Connection::$state`.
   - `Wallet\WalletKeyType: string` — `ViewKey = 'view_key'`,
     `SpendKey = 'spend_key'`, `Mnemonic = 'mnemonic'` — parameter type for
     `Wallet::queryKey()`, replacing its `in_array()` validation +
     `InvalidArgumentException`.
   - `Wallet\TransferPriority: int` — `Default = 0`, `Unimportant = 1`,
     `Normal = 2`, `Elevated = 3` (verify names against monero source) — parameter
     type for `Wallet::transfer()` / `transferSplit()` / `sweepAll()`.
   - `Wallet\SslSupport: string` — `Enabled = 'enabled'`, `Disabled = 'disabled'`,
     `Autodetect = 'autodetect'` — parameter type for `Wallet::setDaemon()`.
   - `Wallet\TransferType: string` — `In = 'in'`, `Out = 'out'`,
     `Pending = 'pending'`, `Failed = 'failed'`, `Pool = 'pool'`, `Block = 'block'`
     (coinbase) — parameter type for `Wallet::getTransfers()` /
     `incomingTransfers()`; also ready for when `get_transfers` responses get typed
     models.
   - CONSIDERED AND REJECTED as enums: response `status` strings (open set — "OK",
     "BUSY", "Failed", "PAYMENT REQUIRED", free-text errors) and
     `MiningStatus::$powAlgorithm` (open set). These stay `string`; an enum here
     would turn an unknown-but-harmless new value into a hydration failure.
4. **Hydration via `FactoryRegistry::addFactory()` invokable factory classes**, the
   same pattern as bh-wp-bitcoin-gateway's
   [`JsonMapper_Money`](https://github.com/BrianHenryIE/bh-wp-bitcoin-gateway/blob/master/includes/api/helpers/jsonmapper/class-jsonmapper-money.php)
   and
   [`JsonMapper_DateTimeInterface`](https://github.com/BrianHenryIE/bh-wp-bitcoin-gateway/blob/master/includes/api/helpers/jsonmapper/class-jsonmapper-datetimeinterface.php)
   — adapted for shape: monerod sends bare integers (atomic units / epoch seconds),
   not `{amount, currency}` / `{date, timezone}` objects. Factories are registered
   at the ONE hydrator construction site from PHP84_READONLY_MODELS_PLAN Phase 3.
   Invalid inputs throw (consistent with that plan's revised design decision 5 —
   no fabricated values).
5. **Request-side serialization:** when a `MoneroAmount` is sent in request params
   (e.g. `transfer` destinations), serialize via `toAtomicUnitsString()` cast to
   `int` when within `PHP_INT_MAX`, and THROW if it exceeds it (monerod request
   amounts above 9.2M XMR are not real; a silent float is). Enums serialize via
   `->value`.
6. **Wire behavior must not change.** The integration suite and its
   `MoneroRegtestFixture` constants (values, not PHP types) are the proof.

## Phase 1 — Add brick/math and the MoneroAmount value object

1. `composer require brick/math` pinned to a caret constraint of the current version
   (verify latest; record it).
2. `src/MoneroAmount.php` per design decision 1, fully documented.
3. `tests/unit/MoneroAmountUnitTest.php`:
   - `fromXmr('1.23')` ⇒ atomic `'1230000000000'` (the exact conversion
     `Wallet::_transform()` used to truncate — see INTEGRATION_TEST_PLAN.md bug #1).
   - `fromXmr('0.0000000000001')` (13 decimals) throws.
   - Round-trip of a value ABOVE `PHP_INT_MAX`, e.g. `'18446744073709551615'`
     (uint64 max): `fromAtomicUnits(string)` → `toAtomicUnitsString()` lossless.
   - `fromXmr('1.230000000000')` equals `fromXmr('1.23')`.
   - Arithmetic and comparison methods.

**Acceptance criteria:** unit suite green; `composer lint` clean on new files.

## Phase 2 — Big-integer-safe response decoding

`RpcClient::run()` currently lets `simpod/json-rpc`'s `ResponseExtractor`
`json_decode()` the body and then re-encodes the result — precision for > int64
values is destroyed there, before any mapper runs.

1. Decode the raw response body once, with `JSON_BIGINT_AS_STRING`
   (`json_decode($body, false, 512, JSON_BIGINT_AS_STRING | JSON_THROW_ON_ERROR)`),
   and extract `result` / `error` from that decode; stop round-tripping through
   `ResponseExtractor` for the result payload (keep it, or its error semantics, for
   error extraction if convenient — but the RESULT path must be single-decode).
2. Unit test with a fixture response containing `"reward": 18446744073709551615`:
   assert the hydrated `MoneroAmount` (Phase 3) — or, until then, the intermediate
   string — is exact. This test is the permanent regression net for the float trap.

**Acceptance criteria:** unit suite green; one full `make integration` cycle green
(proves ordinary responses still decode identically).

## Phase 3 — Hydration factories

1. `src/JsonMapperFactory/MoneroAmountFactory.php` — invokable: accepts `int` or
   numeric-`string` (the `JSON_BIGINT_AS_STRING` output) → `MoneroAmount`; anything
   else throws.
2. `src/JsonMapperFactory/DateTimeImmutableFactory.php` — invokable: accepts `int`
   epoch seconds → `DateTimeImmutable('@' . $seconds)` (inherently UTC). The
   `0 → null` rule is handled by the nullable property type plus mapper behavior —
   verify in a test which of factory-vs-mapper must implement it, and document.
3. Enums: verify whether the pinned json-mapper version natively hydrates
   `BackedEnum` promoted parameters (`tryFrom`); if not, add per-enum factories. An
   unknown enum value must throw (closed sets, decision 3), with the offending value
   in the message.
4. Register all factories at the single hydrator construction site.
5. Unit tests per factory, including failure modes.

**Acceptance criteria:** unit suite green; factories registered in exactly one place.

## Phase 4 — Sweep the models and method signatures

Amount fields → `MoneroAmount` (grep for others; this inventory is from the current
models): `BlockHeader::$reward`, `BlockTemplate::$expectedReward`,
`MiningStatus::$blockReward`, `Wallet\Balance::$balance`,
`Wallet\Balance::$unlockedBalance`, `TransactionPoolStatsStats::$feeTotal`.
NOT amounts: `Info::$credits` / `ResponseBase::$credits` (RPC-payment credits),
difficulties, sizes, weights, counts — stay `int` (difficulties keep their
`…Top64` split fields as-is).

Timestamp fields → per design decision 2 (convert list + do-not-convert list there).

Enum fields/parameters → per design decision 3, including the `Daemon`/`Wallet`
method signature changes (`queryKey(WalletKeyType $keyType)`,
`transfer(MoneroAmount $amount, string $address, ..., TransferPriority $priority ...)`,
`setDaemon(..., SslSupport $sslSupport ...)`, `getTransfers(TransferType[] ...)`).
`transfer()`/`transferSplit()` stop accepting XMR-denominated strings entirely —
callers construct `MoneroAmount::fromXmr('1.23')`; delete `Wallet::_transform()` and
its unit test coverage moves to `MoneroAmountUnitTest`.

All transferred/edited docblocks follow PHP84_READONLY_MODELS_PLAN design decision 3:
nothing lost, evidence links kept.

**Acceptance criteria:** no `int` property whose docblock says "atomic units" (grep);
unit suite compiles and is green after the Phase 5 test sweep (same
single-branch caveat as the readonly plan).

## Phase 5 — Update tests and fixtures

1. `tests/integration/MoneroRegtestFixture.php`: amount constants become atomic-unit
   STRINGS (e.g. `TRANSFER_AMOUNT_ATOMIC_UNITS = '1230000000000'`) with a
   convenience `getTransferAmount(): MoneroAmount`. Values unchanged.
2. `tests/integration/seed-monero-regtest-chain.php`: construct amounts via
   `MoneroAmount::fromXmr('1.23')`; manifest continues to store atomic units as
   JSON strings/numbers (document which).
3. Integration + unit test assertions: `assertSame(4572171347974391, ...)` becomes
   `assertTrue(MoneroAmount::fromAtomicUnits('4572171347974391')->isEqualTo(...))`
   or `assertSame('...', $x->toAtomicUnitsString())` — implementer picks ONE style
   and uses it consistently. Timestamp assertions compare
   `->getTimestamp()`/`DateTimeImmutable` equality; nettype assertion becomes
   `NetType::Fakechain`.
4. `MappersTest` fixture JSONs already contain real amounts/timestamps — they now
   double as factory regression tests; add one fixture with a > `PHP_INT_MAX`
   amount if none exists.

**Acceptance criteria:** `composer test` green; full `make integration` cycle green
TWICE; fixture VALUES unchanged (only their PHP representation).

## Phase 6 — Docs and verification

1. README usage example updated (e.g. `$balance->unlockedBalance->toXmr()`), note on
   brick/math and why `int` is unsafe for atomic units (one sentence).
2. Contributing section: new-model checklist gains "amounts are `MoneroAmount`,
   epoch timestamps are `DateTimeImmutable`, closed value sets are enums, durations
   and `unlock_time` are `int`".
3. `composer lint` (PHPStan should approve of the removed `int` ambiguity); PhpDoc
   preservation spot-check on every file touched in Phase 4.
4. Add an "Implementation notes" addendum to this file recording deviations.

**Acceptance criteria:** all suites green; lint no new errors; addendum written.

## Known risks / gotchas for the implementing agent

- The `ResponseExtractor` bypass (Phase 2) is the riskiest edit — it touches every
  RPC call. Do it before the model sweep so the integration suite validates it in
  isolation.
- `json_decode` with `JSON_BIGINT_AS_STRING` changes big ints to strings for ALL
  fields, including heights/difficulties that remain `int`-typed: confirm the mapper
  coerces numeric strings to `int` params (spike it; if not, the factories/mapper
  need a numeric-string-to-int accommodation for int-typed params).
- `DateTimeImmutable('@…')` ignores timezones by definition (UTC) — do not pass a
  timezone parameter and do not use the server's local zone anywhere.
- Do not convert `unlock_time`. It is a height OR a timestamp (threshold
  500000000). This is the classic Monero client bug; the docblock warning is the
  deliverable.
- Enum sets must be verified against the monero source (`connection_basic`,
  wallet2 priority) before being finalized — a wrongly-closed set turns valid
  responses into exceptions.

## Implementation notes (added after implementation)

Deviations and clarifications discovered while executing this plan:

1. **Enums hydrate natively — no per-enum factories.** json-mapper (v2.25.1) already maps a
   `BackedEnum`-typed property via `Enum::from()`, which throws `\ValueError` on an unknown
   value — exactly the closed-set behaviour wanted. Phase 3's "add per-enum factories if
   needed" was therefore unnecessary; only `MoneroAmount` and `DateTimeImmutable` factories
   are registered.
2. **The FactoryRegistry is built by hand, not via `withNativePhpClassesAdded()`.** That
   helper pre-registers a *string*-based `DateTimeImmutable` factory and `addFactory()`
   throws on a duplicate class name, so an epoch-*integer* `DateTimeImmutable` factory could
   not be added on top of it. `buildResponseMapper()` now constructs the registry directly
   (`stdClass` + `MoneroAmount` + epoch `DateTimeImmutable`). The native `DateTime`
   (non-immutable) factory is dropped; no model uses it.
3. **`0 → null` applies to ALL converted epoch fields, so every one is `?DateTimeImmutable`.**
   json-mapper resolves factories by TYPE, not by field, so a single `DateTimeImmutable`
   factory cannot be nullable for `oldest` yet non-null for `BlockHeader::$timestamp`. Since
   monerod uses `0` as the "unset" epoch sentinel (and the genesis block's timestamp is 0),
   the rule is applied uniformly: `0 → null`, and `BlockHeader::$timestamp`,
   `Info::$adjustedTime`, `Info::$startTime`, `TransactionPoolStatsStats::$oldest` are all
   `?DateTimeImmutable`. (Trade-off: these drop out of `assertResponseComplete()`'s
   required-field check; none are payment-critical.)
4. **`TransferPriority` has five values (0–4), not four** — the source `fee_priority` enum is
   `Default, Unimportant, Normal, Elevated, Priority`. **`ConnectionState`** wire values are
   `before_handshake` / `synchronizing` / `standby` / `normal` (from
   `get_protocol_state_string()`, NOT the `state_*` C++ enumerator names) plus an `unknown`
   fallback. Both verified against the vendored monero source.
5. **`incoming_transfers` needs its own enum.** Its `transfer_type` value set is
   `all` / `available` / `unavailable` — distinct from `get_transfers`' `in/out/pending/…`.
   Added `Wallet\IncomingTransferType`; the source plan had wrongly folded both into
   `TransferType`.
6. **Request-side serialization** lives in `Wallet::amountToRequestInt()` (throws above
   `PHP_INT_MAX`). `sweepSingle()` was also retyped (it used `_transform` and had a latent
   undefined-`$accountIndex` bug, now an explicit parameter); `makeUri()` too.
7. **`MappersTest` now decodes fixtures with `JSON_BIGINT_AS_STRING`** to mirror
   `RpcClient::run()`, and gained a `> PHP_INT_MAX` amount regression test.
8. **Out of scope — observed:** `Daemon\PeerListEntry::$id` is a `uint64` peer identifier that
   overflows signed int64; hydrating the `get_peer_list` fixture emits float-cast warnings
   (pre-existing, unchanged in count). It is neither a currency amount nor a timestamp, so it
   was left `int`. Typing it `string` would be a sensible follow-up but is outside this plan.
