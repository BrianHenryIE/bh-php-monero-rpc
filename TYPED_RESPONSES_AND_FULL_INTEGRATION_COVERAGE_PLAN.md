# Plan: typed responses everywhere + an integration test for every method

Implementation plan for closing the two remaining coverage gaps in
`brianhenryie/bh-php-monero-rpc`: methods still returning `stdClass` (or untyped),
and methods without integration tests. Written to be executed step-by-step by an AI
coding agent. Each phase has acceptance criteria.

**Ordering:** implement AFTER PHP84_READONLY_MODELS_PLAN.md and
BRICK_MATH_DATETIME_ENUMS_PLAN.md — every new model follows those conventions
(readonly, promoted params, required-fields-throw, `MoneroAmount`,
`DateTimeImmutable`, enums).

## Is "an integration test for every method" reasonable? Yes, with definitions.

Every public method gets an integration test, where "test" is one of three
legitimate kinds:

- **LIVE** — call the method against the seeded stack and assert typed values
  (the default; the overwhelming majority).
- **DESTRUCTIVE** — live test against a dedicated *sacrificial* container that the
  test is allowed to kill (`stopDaemon`, `stopWallet`).
- **ERROR-CONTRACT** — the RPC is removed/nonfunctional upstream or a valid input
  cannot reasonably be constructed client-side; the test asserts the SPECIFIC
  documented error monerod returns (e.g. `startSaveGraph` → HTTP 404;
  `submitBlock` with a malformed blob → "Wrong block blob"). This is a real test:
  it pins the library's behavior for the failure the user will actually see.

What is NOT acceptable: skipped tests, or methods absent from the suites. The
Phase 8 audit enforces one test per public method mechanically.

## Ground rules

1. **The seed script is FROZEN.** `seed-monero-regtest-chain.php` and every
   `MoneroRegtestFixture` constant stay byte-identical; all new scenarios
   (integrated-address payments, sweeps, multisig, pool inspection) are built
   inside mutating tests that re-derive their expectations. This keeps the three
   existing suites' determinism guarantees untouched.
2. **Typing and testing are one work unit.** A method's stdClass return is replaced
   by a readonly model (+ fixture JSON in `tests/_data` + `MappersTest` entry)
   in the same change as its integration test, per the README contributing flow.
3. Existing suite conventions apply: mutating tests restore daemon settings in
   `finally`; throwaway wallets use `uniqid()` filenames and
   `forgetOpenWalletState()`; no `markTestSkipped` anywhere.
4. Where a method is BROKEN as currently written (known: `Wallet::labelAddress()`
   sends `index: int` instead of `index: {major, minor}`), fix the request
   construction as part of its work unit.

## Phase 1 — Infrastructure

1. **Sacrificial containers.** Add to docker-compose.yml:
   `monero-daemon-sacrificial` (regtest flags as the peer daemon, no exclusive
   peering needed, host port `127.0.0.1:28089:18081`) and
   `monero-wallet-rpc-sacrificial` (pointed at the sacrificial daemon, host port
   `127.0.0.1:58085:18083`) — both with `restart: "no"` (a stopped daemon MUST
   stay stopped for the assertion). Mirror them in any local process harness.
2. **New PHPUnit suite `integration-destructive`** (`tests/integration-destructive/`),
   run LAST in `composer test-integration`. Base class tolerates the sacrificial
   services being already-dead only by failing with an actionable "restart the
   stack" message.
3. **`Wallet::transfer()`/`transferSplit()` gain `get_tx_hex`, `get_tx_metadata`,
   and `get_tx_keys` request parameters** (currently not exposed) — prerequisite
   for testing `relayTx()` and `describeTransfer()`.
4. Fix `Wallet::labelAddress(int $accountIndex, int $addressIndex, string $label)`
   → sends `index: {major: ..., minor: ...}`.

**Acceptance criteria:** stack (6 containers) healthy via `make integration-up`;
empty destructive suite wired into composer scripts; existing suites green.

## Phase 2 — Daemon: remaining methods

Already LIVE-tested (no action beyond typing where noted): getBlockCount,
getHeight, onGetBlockHash, getLastBlockHeader, getBlockHeaderByHash/Height,
getBlockByHash (**needs typed model — currently stdClass**), getBlockByHeight,
getBlockTemplate, getInfo, getConnections, getPeerList, getTransactions (**type
it**), getTransactionPoolStats, getAltBlocksHashes, getLimit, setLimit, inPeers,
getBans, setBans (**type it**), getHardForkInfo (**type it**), miningStatus,
saveBc, generateBlocks, startMining (**type it**), stopMining, flushTxPool
(**type it**).

To add:

| Method | Test kind | Scenario | New typed model |
|---|---|---|---|
| `getTransactionPool()` | LIVE (mutating) | transfer with `do_not_relay=false`, do NOT mine; assert the tx appears in the pool, then mine to clean up | `TransactionPool` |
| `isKeyImageSpent()` | LIVE (read-only) | key image exported from the miner wallet for the seed transfer's spent input → status SPENT(1); a fresh unspent output's → UNSPENT(0). Spent-status values become an int-backed enum `KeyImageSpentStatus` | `KeyImageSpent` |
| `getOuts()` | LIVE (read-only) | output indices taken from `getTransactions(manifest txid)` → assert keys/heights returned | `Outs` |
| `sendRawTransaction()` | LIVE (mutating) | build via `transfer(do_not_relay: true, get_tx_hex: true)`, submit the hex, assert accepted + eventually mined; plus ERROR-CONTRACT for garbage hex | `SendRawTransactionResult` |
| `submitBlock()` | LIVE attempt, else ERROR-CONTRACT | try submitting the unmodified `getBlockTemplate` blob — at `--fixed-difficulty 1` it may pass PoW; if that proves flaky across runs, keep only the malformed-blob error assertion and document why | `ResponseBase` |
| `outPeers()` | LIVE (mutating) | set/read/restore, like the existing `inPeers`/`setLimit` pattern | `OutPeers` |
| `setLogHashRate()` | LIVE (mutating) | set visible while mining on the peer daemon (piggyback the existing mining test), restore | — |
| `setLogLevel()` | LIVE (mutating) | set 1, assert OK, restore 0 | — |
| `setLogCategories()` | LIVE (mutating) | set a category string, assert echo in response, restore | `LogCategories` |
| `stopDaemon()` | DESTRUCTIVE | stop the sacrificial daemon; assert OK response, then RPC connection refused | — |
| `startSaveGraph()` / `stopSaveGraph()` | ERROR-CONTRACT | removed upstream (404, per the old contract-test notes). Mark both `@deprecated` in the library, assert the 404/error surface; consider removal at v1.0 | — |

**Acceptance criteria:** all Daemon methods have a test in one of the three kinds;
no Daemon method returns bare `stdClass`; suites green via `make integration`.

## Phase 3 — Wallet: transfers, sweeps, pool

| Method | Test kind | Scenario | New typed model |
|---|---|---|---|
| `transfer()` | already LIVE | type the response (currently stdClass) incl. `MoneroAmount` fee/amount | `TransferResult` |
| `transferSplit()` | already LIVE | type it | `TransferSplitResult` |
| `relayTx()` | LIVE (mutating) | `transfer(do_not_relay: true, get_tx_hex: true)` → `relayTx(hex)` → assert txid matches and confirms after mining | `RelayTxResult` |
| `sweepAll()` | LIVE (mutating) | restore a throwaway wallet from a fresh seed, fund it 0.01 XMR from the miner, mine 10, sweep all back to the miner address, assert throwaway balance zero | `SweepAllResult` |
| `sweepSingle()` | LIVE (mutating) | same funded throwaway; sweep one specific key image | `SweepSingleResult` |
| `sweepDust()` / `sweepUnmixable()` | already LIVE / LIVE | modern chains have no dust: assert the empty-result shape explicitly (that IS the contract) | type both |
| `describeTransfer()` | LIVE (mutating) | view-only wallet (existing `generateFromKeys` flow) + `exportOutputs`/`importOutputs` → create `transfer` on the view-only wallet → returns `unsigned_txset` → `describeTransfer` on it; assert destination + amount | `TransferDescription` |
| `getPayments()` / `getBulkPayments()` | LIVE (mutating) | standalone payment IDs are dead upstream, but INTEGRATED addresses still carry an 8-byte payment ID: transfer to `makeIntegratedAddress()` result, mine, then `getPayments(paymentId)` on the recipient | `Payments` |
| `incomingTransfers`, `getTransfers`, `getTransferByTxid` | already LIVE | type them (uses `TransferType` enum, `MoneroAmount`, `DateTimeImmutable` timestamp) | `IncomingTransfers`, `Transfers`, `TransferByTxid` |

## Phase 4 — Wallet: keys, proofs, outputs, misc reads

| Method | Test kind | Scenario | New typed model |
|---|---|---|---|
| `importOutputs()` | LIVE (mutating) | full wallet `exportOutputs` → view-only wallet `importOutputs`; assert count imported | `ImportOutputsResult` |
| `importKeyImages()` | LIVE (mutating) | full wallet `exportKeyImages(true)` → view-only `importKeyImages`; assert view-only wallet's spent amount becomes accurate (this is the whole point of the flow) | `ImportKeyImagesResult` |
| `validateAddress()` | LIVE (read-only) | fixture addresses valid; a corrupted-checksum string invalid; integrated address flagged as such | `AddressValidation` |
| `makeUri()` / `parseUri()` | LIVE (read-only) | round-trip with `MoneroAmount::fromXmr('1.23')`, recipient name, description | `MoneroUri` |
| `getAddressBook()` / `addAddressBook()` / `deleteAddressBook()` | LIVE (mutating) | add on recipient wallet → get by index, assert fields → delete → assert gone (cleanup in `finally`) | `AddressBookEntry` |
| `getTxKey`, `checkTxKey`, proofs (5), `sign`, `verify`, `getAddressIndex`, `getAccounts`, `getAccountTags`, `getLanguages`, `exportOutputs`, `exportKeyImages`, `splitIntegratedAddress`, `getAttribute`, `getTxNotes`, `queryKey` | already LIVE | type every one of them (that is most of the remaining stdClass surface) | one model each |
| `store`, `stopWallet` precursors: `createWallet`, `openWallet`, `closeWallet`, `changeWalletPassword`, `createAccount`, `labelAccount`, `tagAccounts`, `untagAccounts`, `setAccountTagDescription`, `createAddress`, `setTxNotes`, `setAttribute`, `rescanSpent`, `rescanBlockchain`, `refresh`, `autoRefresh`, `setDaemon`, `generateFromKeys`, `restoreDeterministicWallet` | already LIVE | type the non-void ones (`createAccount` → `CreatedAccount`, `createAddress` → `CreatedAddress`, `generateFromKeys` → `GeneratedWallet`, …) | as listed |
| `labelAddress()` | LIVE (mutating) | after the Phase 1 fix: label a created subaddress, read back via `getAddress()` | — |
| `Wallet::startMining()` / `Wallet::stopMining()` | LIVE (mutating) | wallet-rpc proxies to its daemon; same pattern as the daemon mining test (~20s `stop_mining`; budget for it) | — |
| `stopWallet()` | DESTRUCTIVE | on the sacrificial wallet-rpc: assert OK, then connection refused | — |

## Phase 5 — Multisig (its own phase; genuinely intricate)

2-of-2 between throwaway wallets on the two wallet-rpc servers:
`isMultisig` (false) → `prepareMultisig` → `makeMultisig` (exchange infos) →
`exchangeMultisigKeys` rounds until the multisig address is final → `isMultisig`
(true, 2/2) → fund the multisig address from the miner, mine 10 →
`exportMultisigInfo`/`importMultisigInfo` both directions → `transfer` from
wallet A returns `multisig_txset` → `signMultisig` on wallet B →
`submitMultisig` → mine → assert the destination received.

- Type every multisig response (`MultisigInfo`, `PreparedMultisig`, `MadeMultisig`,
  `SignedMultisig`, …).
- `finalizeMultisig()`: verify against the pinned monerod whether it still exists
  (it was deprecated in favor of `exchange_multisig_keys`); if removed upstream,
  `@deprecated` + ERROR-CONTRACT test, like `startSaveGraph`.
- Multisig on regtest requires the wallets to see the same chain — both wallet-rpc
  servers already point at synced daemons; add generous `pollUntil`s.

**Acceptance criteria (phases 2–5):** every method in the phase's table has its
test and its typed model; fixture JSON saved in `tests/_data` + `MappersTest`
entry per model; `make integration` green after each phase.

## Phase 6 — Error-path integration tests (cross-cutting)

One test per error CATEGORY (not per method): RPC error object surfaced
(`getBlockHeaderByHash('invalid')`), wallet-rpc business error (`openWallet`
wrong password), removed endpoint (404), malformed input rejected
(`sendRawTransaction('zz')`), and — once implemented — auth failure when
credentials are wrong. Assert exception TYPE and message content, pinning what
consumers can catch. (The generic `Exception` in `RpcClient::run()` should become
a `MoneroRpcErrorException` carrying code + message as part of this phase.)

## Phase 7 — CI budget

The suites will grow past the current ~1 minute. Measure; if the full cycle
exceeds ~10 minutes in GitHub Actions, split the workflow into parallel jobs by
suite (each job: up → seed → one suite → down) rather than weakening tests.
Destructive suite always runs in its own job (it kills containers).

## Phase 8 — Coverage audit (mechanical, permanent)

Add `tests/audit-integration-coverage.php` (run in CI after the suites): reflect
all public methods of `Daemon` and `Wallet`, grep the integration/destructive test
sources for each method name, and FAIL listing any method with no reference.
Maintain an explicit in-script allowlist for methods whose coverage is indirect
(e.g. `openWallet` exercised via every `openMinerWallet()` call) — the allowlist
entry must name the covering test. This turns "every method has a test" from an
aspiration into a CI invariant.

**Acceptance criteria:** audit script passes with an empty (or fully-justified)
allowlist; `composer test`, `composer lint`, `make integration` green; README
Contributing updated ("new method = typed model + fixture JSON + MappersTest entry
+ integration test — the audit will fail your PR otherwise").

## Known risks / gotchas

- `submitBlock` LIVE viability at difficulty 1 is an experiment; time-box it.
- Multisig RPC behavior changed several times across monerod versions; validate
  each step's response shape against the PINNED version, not against docs.
- Sweeps and multisig funding MUST use throwaway wallets — never sweep the fixture
  miner wallet (later read-only tests depend on its manifest balance).
- Pool-inspection tests and `relayTx` leave the pool non-empty on failure; always
  mine-to-confirm or `flushTxPool` in `finally`.
- The audit script must reflect on `Daemon`/`Wallet` — not parse files — so
  renames can't silently break it.

## Implementation notes (added after implementation)

Deviations and findings from executing this plan against the pinned `MONERO_VERSION`
(`v0.18.5.0`). Final state: unit 97 tests; `integration` 56 / `integration-mutating` 39 /
`integration-destructive` 2 all green; coverage audit passes; PHPStan below the
pre-work baseline; ~106 readonly model classes.

1. **Multisig is only partially reachable over RPC (the biggest deviation).** v0.18.5
   gates multisig behind `enable-multisig-experimental`, which is settable ONLY via
   `monero-wallet-cli` (`set enable-multisig-experimental 1`) — there is no
   `monero-wallet-rpc` flag or RPC method for it. So `is_multisig`, `prepare_multisig`
   and `make_multisig` work LIVE (a test drives them to a 2/2 multisig wallet), but
   `exchange_multisig_keys`, `export/import_multisig_info`, `finalize_multisig`,
   `sign_multisig` and `submit_multisig` all error ("multisig is disabled" or "not yet
   finalized"). They are covered by an ERROR-CONTRACT data-provider test and typed from
   monero docs (no LIVE fixtures possible). `finalize_multisig` still EXISTS (errors -48,
   not 404) so it is `@deprecated`, not removed. The full fund → sign → submit flow the
   plan describes is not achievable over wallet-rpc on this version.
2. **`submitBlock` LIVE at `--fixed-difficulty 1` is reliable**, not flaky — the
   unmodified `getBlockTemplate` blob is accepted and mines a block. Both the LIVE and
   the malformed-blob ERROR-CONTRACT tests are kept. `submitBlock` returns
   `{block_id, status, untrusted}`, so it is typed `SubmitBlockResult` (not the plan's
   `ResponseBase`).
3. **Deviations from the plan's model list, driven by real response shapes:**
   `getBlockByHash` reuses the existing `Block`; `sweepUnmixable` reuses `SweepDust`;
   `make_multisig`/`exchange_multisig_keys` share one `MultisigResult`;
   `getBlockTemplate`→`SubmitBlockResult` as above. `incoming_transfers` needed its own
   value set vs `get_transfers` (handled in the earlier brick/math work as
   `IncomingTransferType`).
4. **`get_transfer_by_txid` json-mapper limitation:** the pinned json-mapper
   mis-hydrates a class holding BOTH a single `Transfer` and a `Transfer[]` of the same
   type (a shared-element caching collision). `TransferByTxid::$transfer` is fully typed;
   `$transfers` (the rarely-used per-subaddress breakdown) is left raw `stdClass[]` and
   documented.
5. **Latent request-construction bugs fixed while typing** (ground rule 4):
   `relayTx` called `relay_tx_method` then a bogus paramless `relay_tx`; `sweepSingle`
   never sent `key_image` and sent invalid `account_index`/`below_amount`/empty
   `payment_id`; `flushTxPool` sent bare ids instead of `{txids:[…]}`; `getBulkPayments`
   built no working params; `labelAddress` sent `index:int` not `{major,minor}`;
   `addAddressBook` required a deprecated `payment_id`; `transfer_split` used the singular
   `get_tx_key` flag; `outPeers(-1)` returns an empty body (must pass a concrete limit).
6. **`export_outputs`/`get_languages`** take no params, so the typed calls pass
   `runJsonRpc('m', null, Type::class)` (a `null` params slot before the type), not
   `runJsonRpc('m', Type::class)`.
7. **`MoneroRpcErrorException`** (Phase 6) extends `\Exception`, so the many existing
   `catch (\Exception)` / `expectException(\Exception)` sites keep working while gaining a
   typed code/message/data. Removed-endpoint 404s still surface as `\JsonException`
   (empty body), which is a distinct error category the tests pin separately.
8. **CI (Phase 7):** the serial cycle had grown near ~10 min (seeding ~120 blocks
   dominates; the mutating suite mines heavily), so the workflow is a parallel per-suite
   matrix; the destructive suite runs in its own job (no seed) since it stops the
   sacrificial containers.
9. **Coverage audit allowlist (Phase 8):** `openWallet` (base-class helper),
   `setAuthorizationCredentials` (unimplemented TODO stub), and the six experimental-gated
   multisig methods (driven by data-provider dynamic dispatch, invisible to the literal
   `->method(` grep) are allowlisted with their covering test named.
