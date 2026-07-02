# Integration test environment.
# See INTEGRATION_TEST_PLAN.md and docker-compose.yml.

# Start the Monero regtest containers and wait until healthy.
integration-up:
	docker compose up -d --wait

# Build the deterministic regtest chain and write tests/_data/integration/manifest.json.
integration-seed:
	php tests/integration/seed-monero-regtest-chain.php

# Destroy the containers AND all chain/wallet state.
integration-down:
	docker compose down -v

integration-logs:
	docker compose logs

# Full cycle: clean slate, start, seed, run integration suites, tear down.
integration:
	$(MAKE) integration-down
	$(MAKE) integration-up
	$(MAKE) integration-seed
	MONERO_INTEGRATION_TESTS=1 composer test-integration
	$(MAKE) integration-down

clear-dep:
	rm -rf ./vendor/
