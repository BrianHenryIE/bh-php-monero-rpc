#!/usr/bin/env php
<?php

/**
 * Coverage invariant: every public RPC method of Daemon and Wallet must be referenced by at
 * least one integration/destructive test.
 *
 * This reflects the classes (rather than parsing their source) so method renames cannot
 * silently pass the audit, then greps the integration test sources for `->method(`. Methods
 * covered only indirectly (e.g. openWallet via the openMinerWallet() helper) are listed in
 * ALLOWLIST with the name of the covering test. Any public method that is neither referenced
 * nor allowlisted fails the audit.
 *
 * Run after the integration suites in CI:  php tests/audit-integration-coverage.php
 *
 * @package brianhenryie/bh-php-monero-rpc
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use BrianHenryIE\MoneroRpc\Daemon;
use BrianHenryIE\MoneroRpc\Wallet;

/**
 * Methods whose coverage is indirect; each entry names the test (or helper) that exercises it.
 *
 * @var array<string, string>
 */
const ALLOWLIST = [
    // Exercised by the openMinerWallet()/openRecipientWallet() base-class helpers on every
    // wallet test, and directly by testOpenWalletFailureSurfacedAsTypedException.
    'openWallet' => 'MoneroRpcIntegrationTestCase::openMinerWallet()',
    // No RPC call yet — a documented TODO stub (see RpcClient::setAuthorizationCredentials()).
    'setAuthorizationCredentials' => 'not yet implemented (TODO stub, no RPC)',
    // Called via dynamic dispatch ($wallet->{$method}(...)) in the data-provider test that
    // pins the experimental-multisig error contract, so the literal `->method(` grep misses them.
    'exchangeMultisigKeys' => 'testMultisigExperimentalStepsAreGated (data provider)',
    'exportMultisigInfo' => 'testMultisigExperimentalStepsAreGated (data provider)',
    'importMultisigInfo' => 'testMultisigExperimentalStepsAreGated (data provider)',
    'finalizeMultisig' => 'testMultisigExperimentalStepsAreGated (data provider)',
    'signMultisig' => 'testMultisigExperimentalStepsAreGated (data provider)',
    'submitMultisig' => 'testMultisigExperimentalStepsAreGated (data provider)',
];

$testDirs = [
    __DIR__ . '/integration',
    __DIR__ . '/integration-mutating',
    __DIR__ . '/integration-destructive',
];

$testSource = '';
foreach ($testDirs as $dir) {
    foreach (glob($dir . '/*.php') ?: [] as $file) {
        $testSource .= file_get_contents($file);
    }
}

$missing = [];
foreach ([Daemon::class, Wallet::class] as $class) {
    $reflection = new ReflectionClass($class);
    foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        // Only audit methods DECLARED on Daemon/Wallet themselves (skip inherited RpcClient
        // plumbing and the constructor).
        if ($method->getDeclaringClass()->getName() !== $class) {
            continue;
        }
        if ($method->isConstructor()) {
            continue;
        }

        $name = $method->getName();
        if (array_key_exists($name, ALLOWLIST)) {
            continue;
        }

        // Referenced as a method call in any integration test source.
        if (!str_contains($testSource, '->' . $name . '(')) {
            $missing[] = $reflection->getShortName() . '::' . $name . '()';
        }
    }
}

if ($missing !== []) {
    fwrite(STDERR, "Integration coverage audit FAILED. These public methods have no integration test:\n");
    foreach ($missing as $m) {
        fwrite(STDERR, "  - {$m}\n");
    }
    fwrite(STDERR, "\nAdd an integration/destructive test that calls the method, or add an ALLOWLIST\n");
    fwrite(STDERR, "entry (with the covering test named) in tests/audit-integration-coverage.php.\n");
    exit(1);
}

echo "Integration coverage audit passed: every public Daemon/Wallet method is tested.\n";
exit(0);
