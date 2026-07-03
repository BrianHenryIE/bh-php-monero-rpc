# Integration Test Plan: Deterministic Monero Nodes in Docker

Implementation plan for adding true integration tests to `brianhenryie/bh-php-monero-rpc`.
Written to be executed step-by-step by an AI coding agent (Claude Sonnet 4.6). Each phase has
acceptance criteria; do not start a phase until the previous phase's criteria pass.

## Background and current state (read first)

- This library is a PSR-18 Monero RPC client: `src/Daemon.php` (daemon RPC, mostly strongly
  typed) and `src/Wallet.php` (wallet RPC, mostly returns `stdClass`).
- `tests/unit/` mocks RPC responses using JSON fixtures in `tests/_data/`.
- `tests/contract/` contains tests that hit live nodes, but they are **not deterministic**:
  they skip silently when nodes are absent, they shell out to a locally installed
  `monerod`/`monero-wallet-cli` binary via `ContractTestCase::extractFromCli()` to compute
  expected values, and several are permanently `markTestSkipped`.
- `docker-compose.yml` exists with two daemons + two wallet-rpc containers, but it has bugs:
  - The daemons run `--regtest` while both wallet-rpc containers pass `--stagenet`.
    Regtest uses **mainnet** address format; the wallet-rpc containers must NOT pass
    `--stagenet` (or any nettype flag). This mismatch is one reason integration testing
    stalled.
  - The image `sethsimmons/simple-monerod:latest` is unpinned (and superseded by
    `ghcr.io/sethforprivacy/simple-monerod`).
  - Data dirs under `.docker/` accumulate state between runs, so runs are not reproducible.

## Design decisions (do not re-litigate during implementation)

- [x] **Regtest, offline, fixed difficulty.** Daemons run
   `--regtest --offline --fixed-difficulty 1` (the two-daemon pair uses
   `--add-exclusive-node` instead of `--offline` so P2P methods like `get_connections`
   have a real peer, but neither daemon may reach the internet).
- [x] **Determinism via a seed script + manifest, not a committed binary chain snapshot.**
   Mining regtest blocks with the `generateblocks` RPC is near-instant, so the chain is
   rebuilt from genesis on every `docker compose up` by a seeding step. Block hashes and
   txids differ between runs (timestamps are in the header), so the seed step writes
   everything run-specific to `tests/_data/integration/manifest.json`, which tests read.
   Heights, addresses, and atomic-unit balances ARE identical every run and are asserted
   as hardcoded constants. Do not commit LMDB data dirs (not portable arm64/amd64-safe,
   bloats the repo).
- [x] **Deterministic wallets from fixed mnemonics.** Wallets are restored via the wallet-rpc
   `restore_deterministic_wallet` method from 25-word seeds committed to the repo
   (regtest-only, zero real-world value), so addresses and keys are constants.
- [x] **Pinned images.** `ghcr.io/sethforprivacy/simple-monerod` and
   `ghcr.io/sethforprivacy/simple-monero-wallet-rpc`, pinned to an exact monerod version
   tag (check for the current tag, e.g. `v0.18.4.4` as of mid-2026; both images must use
   the SAME monerod version). Record the pinned version in one place
   (`.env` file read by docker-compose) so bumping it is a one-line change.
- [x] **Integration tests fail loudly.** Unlike the existing contract tests, integration tests
   must `fail()` (not skip) when the environment is unreachable, when
   `MONERO_INTEGRATION_TESTS=1` is set. Without that env var the suite is excluded entirely
   via a separate PHPUnit testsuite, so `composer test` stays fast for unit-only runs.
- [x] **No dependency on locally installed monero binaries.** Delete the
   `extractFromCli()` pattern from new code; expected values come from the manifest or
   from constants.

## Naming conventions

Per project owner preference, use unambiguous, verbose names throughout:
`MoneroDaemonRpcIntegrationTest` not `DaemonTest`; `monero-daemon-primary` /
`monero-daemon-peer` not `monero-1` / `monero-2`; `monero-wallet-rpc-miner` /
`monero-wallet-rpc-recipient` for the wallet containers;
`$minerWalletPrimaryAddress` not `$addr1`.

---

## Phase 1 — Deterministic Docker topology

**Goal:** `docker compose up -d --wait` produces, from nothing, two synced regtest daemons
and two wallet-rpc servers, with no persistent state between runs.

Steps:

- [x] Create `.env` at repo root:
   ```
   MONERO_VERSION=v0.18.4.4   # verify current tag at implementation time
   ```
- [x] Rewrite `docker-compose.yml` (keep the existing file as reference until Phase 8, e.g.
   move it to `scratch/docker-compose-old.yml`). Services:
   - `monero-daemon-primary` — `ghcr.io/sethforprivacy/simple-monerod:${MONERO_VERSION}`,
     command: `--regtest --fixed-difficulty 1 --no-igd --hide-my-port
     --p2p-bind-ip 0.0.0.0 --p2p-bind-port 48080 --rpc-bind-ip 0.0.0.0
     --rpc-bind-port 48089 --confirm-external-bind --rpc-ssl disabled
     --add-exclusive-node monero-daemon-peer:38080 --allow-local-ip
     --non-interactive --log-level 0 --data-dir /home/monero/.bitmonero`.
     Ports: `48089:48089`. Use **named Docker volumes or tmpfs**, not bind mounts to
     `.docker/`, so `docker compose down -v` guarantees a clean slate.
   - `monero-daemon-peer` — same image; ports 38080/38089; mirror flags;
     `--add-exclusive-node monero-daemon-primary:48080`. Ports: `38089:38089`.
   - `monero-wallet-rpc-miner` — `ghcr.io/sethforprivacy/simple-monero-wallet-rpc:${MONERO_VERSION}`,
     command: `--daemon-address monero-daemon-primary:48089 --rpc-bind-port 58083
     --disable-rpc-login --trusted-daemon --daemon-ssl disabled --rpc-bind-ip 0.0.0.0
     --confirm-external-bind --wallet-dir /home/monero/wallets --log-level 0`.
     **No nettype flag** (regtest = mainnet format). Ports: `58083:58083`.
   - `monero-wallet-rpc-recipient` — same, port mapping `58084:58083`, daemon address
     `monero-daemon-peer:38089` (exercises wallet-on-second-daemon paths).
   - Healthchecks on every service so `--wait` works: daemons
     `curl -f http://localhost:<rpc-port>/get_height`; wallet-rpc a JSON-RPC `get_version`
     curl POST. Wallet services `depends_on` their daemon with `condition: service_healthy`.
   - Remove the `php-composer` service and `dns: 8.8.8.8` entries; nothing needs internet.
- [x] Verify flag names against the pinned monerod version (`docker run --rm <image> --help`).
   In particular confirm `--allow-local-ip`, `--hide-my-port`, and whether `--offline`
   conflicts with `--add-exclusive-node` (if exclusive-node peering fails with these flags,
   drop `--hide-my-port`; the requirement is only: peers see each other, internet unreachable).
- [x] Update `Makefile`: `make integration-up` (`docker compose up -d --wait`),
   `make integration-down` (`docker compose down -v`), `make integration-logs`.
   Remove stale targets referencing `.docker/monero_data`.
- [x] Add `.docker/*-data/` cleanup: delete the four committed data directories from git
   (`git rm -r --cached`), add to `.gitignore`.

**Acceptance criteria:**
- `docker compose down -v && docker compose up -d --wait` exits 0 in under ~2 minutes cold.
- `curl http://127.0.0.1:48089/get_height` and `:38089/get_height` both return height 1.
- `curl -X POST http://127.0.0.1:58083/json_rpc -d '{"jsonrpc":"2.0","id":"0","method":"get_version"}'`
  returns a version on both 58083 and 58084.
- After mining on one daemon (next phase proves this), the other daemon syncs to the same height.

## Phase 2 — Seed script and fixture manifest

**Goal:** A single idempotent command builds the known chain state and writes the manifest.

Steps:

- [x] Generate, once, two 25-word regtest mnemonics (e.g. via
   `monero-wallet-cli --offline` in the container, or `create_wallet` then `query_key
   key_type=mnemonic`). Commit them as constants — they are test-only. Store in
   `tests/integration/MoneroRegtestFixture.php` as class constants:
   `MINER_WALLET_MNEMONIC`, `MINER_WALLET_PRIMARY_ADDRESS`, `RECIPIENT_WALLET_MNEMONIC`,
   `RECIPIENT_WALLET_PRIMARY_ADDRESS`, plus the expected post-seed constants added in
   step 4 below.
- [x] Write `tests/integration/seed-monero-regtest-chain.php` (plain PHP CLI script using this
   library's own `Daemon` and `Wallet` classes — the seed script doubles as a smoke test).
   Sequence:
   - [x] `restore_deterministic_wallet` on `monero-wallet-rpc-miner` (filename
      `miner_wallet`, the committed mnemonic, `restore_height: 0`); same for
      `recipient_wallet` on `monero-wallet-rpc-recipient`. Note:
      `Wallet.php` may not yet expose `restoreDeterministicWallet()` — add it (typed
      request, typed response model `Wallet/RestoreDeterministicWalletResult.php` +
      mapper + unit test, following the pattern documented in README "Contributing").
   - [x] `generateblocks` on `monero-daemon-primary`: 70 blocks to
      `MINER_WALLET_PRIMARY_ADDRESS` (coinbase outputs unlock after 60 confirmations;
      70 gives spendable balance). Chain height becomes 71.
   - [x] Wait/poll until `monero-daemon-peer` reports height 71 (sync proof).
   - [x] Refresh miner wallet; assert nonzero unlocked balance.
   - [x] `transfer` exactly `1230000000000` atomic units (1.23 XMR) from miner wallet to
      `RECIPIENT_WALLET_PRIMARY_ADDRESS`; record the txid, tx key, fee.
   - [x] `generateblocks` 10 more blocks to the miner address (confirms the transfer;
      final height 81).
   - [x] Refresh both wallets; assert recipient balance == 1230000000000 exactly.
   - [x] Write `tests/_data/integration/manifest.json` (gitignored — regenerated each run):
      ```json
      {
        "seeded_at": "...",
        "monero_version": "v0.18.4.4",
        "chain_height": 81,
        "genesis_block_hash": "...",
        "block_hashes_by_height": {"1": "...", "70": "...", "81": "..."},
        "transfer_txid": "...",
        "transfer_tx_key": "...",
        "transfer_fee_atomic_units": ...,
        "transfer_block_height": ...,
        "miner_wallet_unlocked_balance_atomic_units": ...,
        "miner_wallet_balance_atomic_units": ...
      }
      ```
   - [x] Idempotency: if the daemon height is already 81 and the manifest exists, exit 0
      without re-seeding; if height is anything else other than 1, exit 1 with a message
      telling the operator to `docker compose down -v` first.
- [x] Add `make integration-seed` (`php tests/integration/seed-monero-regtest-chain.php`) and
   `make integration` (up + seed + run suite + down).
- [x] Run the seed twice from clean state; record which manifest values are identical across
   runs. Heights, addresses, and balances will be; promote those to constants on
   `MoneroRegtestFixture` (e.g. `EXPECTED_CHAIN_HEIGHT_AFTER_SEED = 81`,
   `EXPECTED_RECIPIENT_BALANCE_ATOMIC_UNITS = 1230000000000`,
   `EXPECTED_MINER_REWARD_FIRST_BLOCK_ATOMIC_UNITS = <observed>`). Hashes/txids/fees stay
   manifest-only. Do not hardcode reward amounts from documentation — capture observed
   values; regtest block rewards follow the mainnet emission curve from zero coins.

**Acceptance criteria:**
- From `docker compose down -v`: `make integration-up && make integration-seed` succeeds
  in under ~3 minutes and produces the manifest.
- Running `make integration-seed` again immediately exits 0 (idempotent).
- Two full clean-slate runs produce identical heights/balances and differing block hashes
  (confirming the constants/manifest split is correct).

## Phase 3 — PHPUnit integration suite scaffolding

Steps:

- [x] `phpunit.xml`: split into two testsuites — `unit` (`tests/unit`, `tests/contract` for
   now) and `integration` (`tests/integration`). Default `composer test` runs `unit` only;
   add `composer test-integration` running
   `phpunit --testsuite integration`.
- [x] Create `tests/integration/MoneroRpcIntegrationTestCase.php`:
   - `setUpBeforeClass()`: construct `Daemon` clients for 127.0.0.1:48089 and :38089 and
     `Wallet` clients for :58083 and :58084 (host/ports overridable via env vars
     `MONERO_DAEMON_PRIMARY_RPC_PORT` etc. with these defaults).
   - Load and parse the manifest; **fail** with an actionable message
     ("run `make integration-seed`") if missing or if `get_height` ≠
     `EXPECTED_CHAIN_HEIGHT_AFTER_SEED` (a test may have mutated chain state — see
     ordering note below).
   - Helper `openMinerWallet()` / `openRecipientWallet()` (wallet-rpc holds one open
     wallet at a time per server; tests must explicitly open what they need).
   - No `markTestSkipped` anywhere in `tests/integration/`.
- [x] State-mutation policy (document in the test case's PhpDoc): tests that mutate chain or
   wallet state (mining, transfers, `stop_daemon`) go in dedicated `...MutatingState`
   test classes that re-derive expectations from RPC reads rather than manifest constants,
   and run LAST (PHPUnit `@depends`/`#[Depends]` won't order across classes — instead list
   testsuite directories so mutating classes are in a third suite `integration-mutating`,
   run after `integration` in the composer script). Read-only tests must not assume any
   state beyond the seeded chain.

**Acceptance criteria:** `composer test` green and unchanged in behavior;
`composer test-integration` with the stack up runs (zero tests is fine at this point);
with the stack down it errors loudly rather than skipping.

## Phase 4 — Daemon integration tests

Create `tests/integration/MoneroDaemonRpcIntegrationTest.php` (read-only) covering every
typed `Daemon` method, asserting against constants/manifest:

- `getBlockCount()` == 81 (+1 semantics: verify count vs height carefully; capture in
  Phase 2 which one the constant is).
- `getHeight()`, `getLastBlockHeader()` (height, reward > 0, hash == manifest),
  `getBlockHeaderByHeight(70)` / `getBlockHeaderByHash(manifest hash)` round-trip equality,
  `getBlockByHeight()` / `getBlockByHash()`, `onGetBlockHash(70)` == manifest,
  `getBlockTemplate(minerAddress, 8)` (sane fields), `getInfo()` (nettype/regtest fields,
  height), `getConnections()` ≥ 1 peer (this is why two daemons exist),
  `getPeerList()`, `getTransactions([manifest txid])` (found, sane), `isKeyImageSpent()`,
  `getTransactionPoolStats()`, `getTransactionPool()`, `getAltBlocksHashes()`,
  `getLimit()`/`setLimit()` (setLimit mutates daemon config, not chain — acceptable if it
  restores the prior value in `finally`), `inPeers()`/`outPeers()` (same restore rule),
  `getBans()`/`setBans()` (set then unset), `getHardForkInfo()`, `miningStatus()`
  (not mining), `saveBc()`, `getOuts()`, `sendRawTransaction()` (negative test: garbage hex
  → error surfaced as exception or typed error; pick and assert the actual behavior).

`tests/integration/MoneroDaemonRpcMutatingStateIntegrationTest.php`:
- `generateBlocks(1, minerAddress)` — height increases by 1 on both daemons (sync).
- `startMining()`/`miningStatus()`/`stopMining()` — start, observe active, stop, observe
  stopped (regtest difficulty 1 makes this safe; always stop in `finally`).
- `flushTxPool()`.
- Do NOT test `stopDaemon()` against the shared stack (it kills the container); cover it
  with a unit test only, and note that in the test PhpDoc.

**Acceptance criteria:** suite green from a fresh seed; suite green run twice in a row
(mutating tests must leave state self-consistent or re-derive expectations).

## Phase 5 — Wallet integration tests

`tests/integration/MoneroWalletRpcIntegrationTest.php` (read-only, miner wallet unless
stated):

- `getVersion()`, `getBalance()` (recipient == exact constant; miner ≥ known floor),
  `getAddress()`/`getAddressIndex()` == mnemonic-derived constants, `getHeight()` == 81,
  `queryKey('mnemonic')` round-trips the committed mnemonic, `getTransfers()` /
  `getTransferByTxid(manifest txid)` / `incomingTransfers()` (recipient sees exactly one
  transfer of the exact amount), `getTxKey()`/`checkTxKey()` (proves the 1.23 XMR
  payment), `getTxProof()`/`checkTxProof()`, `getSpendProof()`/`checkSpendProof()`,
  `getReserveProof()`/`checkReserveProof()`, `makeIntegratedAddress()`/
  `splitIntegratedAddress()` round-trip, `getPayments()`/`getBulkPayments()`,
  `sign()`/`verify()` round-trip, `exportOutputs()`, `exportKeyImages()`,
  `getLanguages()`, `getAccounts()`.

`tests/integration/MoneroWalletRpcMutatingStateIntegrationTest.php`:
- `createWallet()`/`openWallet()`/`closeWallet()`/`changeWalletPassword()` with
  `uniqid()`-suffixed filenames (wallet files accumulate in the container volume; fine,
  destroyed on `down -v`).
- `createAccount()`/`labelAccount()`/`tagAccounts()`/`untagAccounts()`/
  `setAccountTagDescription()`, `createAddress()`/`labelAddress()` — on a throwaway wallet.
- `transfer()` end-to-end: send from miner to recipient, `generateBlocks(10, ...)` via the
  daemon client, refresh, assert recipient delta == amount. Also `transferSplit()`,
  `sweepDust()`, `relayTx()` (create with `do_not_relay`, then relay),
  `setTxNotes()`/`getTxNotes()`, `setAttribute()`/`getAttribute()`,
  `store()`, `rescanBlockchain()`, `rescanSpent()`, `refresh()`/`autoRefresh()`,
  `setDaemon()` (point recipient wallet at primary daemon and back).
- `generateFromKeys()` — restore a view-only copy of the miner wallet, assert it sees the
  balance.

Many `Wallet` methods return `stdClass`. Where a test needs a field, prefer adding the
typed model + mapper + unit test (per README pattern) over reaching into `stdClass`; this
plan is also the vehicle for finishing `Wallet` typing. If that balloons a step, land the
integration test against `stdClass` first and file the typing as a TODO in the test
PhpDoc — do not silently drop method coverage.

**Acceptance criteria:** as Phase 4; plus the full sequence `unit → integration →
integration-mutating` green twice consecutively without reseeding.

## Phase 6 — CI (GitHub Actions)

- [x] New workflow `.github/workflows/integration-tests.yml`, on `push` + `pull_request`:
   checkout → setup PHP 8.1 → `composer install` → `docker compose up -d --wait` →
   `make integration-seed` → `composer test-integration` (env
   `MONERO_INTEGRATION_TESTS=1`) → on failure, `docker compose logs` as a step so daemon
   logs appear in the run output → `docker compose down -v`.
- [ ] Cache the Docker images between runs (`docker/build-push-action` is overkill; use
   `actions/cache` on `docker save` output, or simply rely on GHCR pull speed — measure,
   and only add caching if pull exceeds ~1 minute).
- [x] Keep the existing coverage workflow unit-only. Optionally append integration coverage
   later via `phpcov merge` (the repo already depends on `phpunit/phpcov`) — mark as
   stretch, not required.

**Acceptance criteria:** green run on GitHub Actions; a deliberately broken assertion
fails the workflow (push to a branch, observe, revert).

## Phase 7 — Migrate/retire the old contract tests

- [x] Port any assertion from `tests/contract/` that isn't already covered by Phases 4–5 into
   the integration suite (notably the multi-daemon sync scenario in
   `WalletContractTest::test_a()`, which becomes a proper named test).
- [x] Delete `tests/contract/`, `ContractTestCase::extractFromCli()`, `walletExtractFromCli()`,
   and the ANSI-stripping helper. Delete `scratch/docker-compose-old.yml`.
- [x] Update `README.md` "Contributing": document the new workflow
   (`make integration` one-liner; manifest/constants split; how to add a typed model with
   both a unit fixture and an integration assertion).
- [x] Remove unit-suite reliance on `tests/contract` paths in `phpunit.xml`.

**Acceptance criteria:** `grep -r "markTestSkipped\|extractFromCli" tests/` returns nothing;
`composer test && composer test-integration` green.

## Phase 8 — Final verification checklist (run all of it)

- [x] `docker compose down -v && make integration` from a clean clone — green.
- [ ] Run the whole flow a second time without `down -v` — green (idempotence).
- [x] `composer test` with Docker stopped — green and fast (no integration leakage).
- [ ] `composer lint` (phpcs + phpstan) green on all new files.
- [ ] Confirm both daemons have no internet: `docker compose exec monero-daemon-primary
   wget -T 3 -q -O- https://example.com` must fail (add `internal: true` network if not).
- [x] Diff review: no committed binary blobs, no `.docker/*-data` directories, manifest
  gitignored, mnemonics committed only in `MoneroRegtestFixture.php` with a comment that
  they are valueless regtest test seeds.

## Implementation notes (added after implementation, June 2026)

The plan above was implemented with these deviations, discovered against live nodes:

- **120 blocks are mined before the transfer, not 70** (final height **131**, not 81).
  Ring-size-16 transactions need ≥16 *unlocked* decoy outputs; at height 71 only 10
  coinbase outputs were unlocked ("not enough outputs to use").
- **`monero-wallet-rpc` needs `--allow-mismatched-daemon-version`** on regtest: every
  hard fork activates at height 1 and refresh otherwise aborts with "Unexpected hard
  fork version v16 at height 1".
- **Mutating tests live in `tests/integration-mutating/`** (a third PHPUnit testsuite),
  not a subdirectory of `tests/integration/`.
- Determinism was verified across three clean-slate seeds: heights, balances
  (4572171347974391 miner / 1230000000000 recipient), the first block reward
  (35184338534400), and even the transfer fee (10180200000) are identical per run;
  block hashes/txids/tx keys differ (header timestamps) and live in the manifest.
- `monerod` default bandwidth limits changed between versions (8192 down in v0.18.3,
  32768 in v0.18.5), so the read-only `getLimit` test asserts presence, not values;
  exact round-tripping is covered by the mutating `setLimit` test.
- `stop_mining` blocks ~20s while the miner thread shuts down; budget for it in CI.
- Re-running the suites without re-seeding is NOT supported once the mutating suite
  has run (it extends the chain); `make integration` always runs a full
  down → up → seed → test → down cycle.

**Library bugs found and fixed by the integration tests** (each a request-construction
bug that response-mocking unit tests could never catch):

- [x] `Wallet::_transform()` declared `int $amount`, silently truncating fractional
   XMR string amounts ('1.23' → 1 XMR) via PHP int coercion.
- [x] `RpcClient::run()` used bare `array_filter()`, stripping legitimate falsy params —
   e.g. height `0` in `on_getblockhash` ("Wrong parameters, expected height").
- [x] `Daemon::getBlockHeaderByHeight()` passed the raw int instead of `['height' => $h]`.
- [x] `Daemon::getTransactions()` built params and never sent them.
- [x] `Wallet::sign()` sent the field `string` instead of `data` — monerod signed an
   empty string, so signatures never verified against the signed data.
- [x] `Wallet::getReserveProof()` built params and never sent them
   ("Proved amount must be greater than 0").
- [x] `RpcClient::run()` wrapped non-`json_rpc` endpoint params in a JSON-RPC envelope,
   which monerod ignores; "other" endpoints now receive a plain JSON body.
- [x] `Wallet::exportKeyImages()` now accepts `bool $all`; without `all=true` the RPC
   omits `signed_key_images` entirely for previously-exported images.

## Known missing test coverage to track (not all addressed by this plan)

- `Wallet` methods returning `stdClass` have no model-mapper unit tests (`MappersTest`
  covers Daemon models far more than Wallet models); Phase 5 reduces but does not close this.
- `Daemon::stopDaemon()` gets unit coverage only (by design, see Phase 4).
- Error paths: HTTP failures, RPC `error` responses, malformed JSON — currently almost no
  negative-path unit tests for `RpcClient::run()`. Worth a dedicated unit-test pass.
- RPC auth (`--rpc-login` digest auth) is never exercised; both wallet-rpc and daemon
  support it and the client claims to (`RpcClient::setAuthorizationCredentials()` is an
  empty TODO). Stretch: add a fifth container with `--rpc-login user:pass` and one
  auth round-trip test.
- `Wallet::relayTx()` untested: `Wallet::transfer()` does not expose `get_tx_hex`,
  so there is no transaction blob to relay.
- `Wallet::labelAddress()` is broken (sends `index: int`; the RPC requires
  `index: {major, minor}`) and is documented as a gap in the mutating test class.
- `Wallet::sweepAll()`/`sweepSingle()` untested (would empty the fixture miner wallet;
  needs a dedicated throwaway funded wallet).
- Request-construction unit tests: the eight bugs above show that mocking only
  responses leaves the request side untested. A unit-test pass asserting the exact
  request bodies sent (psr-mock records requests) would prevent regressions without
  the Docker stack.
- ZMQ ports are mapped in the old compose file but nothing in the library uses ZMQ; drop them.
- Restricted RPC (`--restricted-rpc`) behavior differences are untested.

## Reference material for the implementing agent

- `generateblocks` RPC (regtest only): params `amount_of_blocks`, `wallet_address`,
  `starting_nonce` — see existing `Daemon::generateBlocks()`.
- Regtest how-to: https://gist.github.com/TheCharlatan/516d45edb70b75758777742541bb4d76
- Daemon RPC docs: https://docs.getmonero.org/rpc-library/monerod-rpc/
- Wallet RPC docs: https://docs.getmonero.org/rpc-library/wallet-rpc/
- Images: https://github.com/sethforprivacy/simple-monerod-docker and
  https://github.com/sethforprivacy/simple-monero-wallet-rpc-docker
- Prior art already vendored in `composer.json` require-dev: `monero-rs/monero-rpc-rs`
  runs its test suite against a regtest monerod + wallet-rpc in Docker — consult its
  compose/CI files in `vendor/monero-rs/monero-rpc-rs` when stuck.
- Monero facts the agent must not forget: coinbase unlocks after 60 confirmations;
  regtest uses mainnet address prefixes; wallet-rpc serves one open wallet at a time;
  amounts are atomic units (1 XMR = 1e12); block hashes are timestamp-dependent and thus
  not reproducible across seeding runs.
