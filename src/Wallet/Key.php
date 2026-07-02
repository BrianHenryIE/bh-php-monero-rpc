<?php

/**
 *
 * To res`monero-wallet-cli --restore-deterministic-wallet` and enter your mnemonic seed.
 */

namespace BrianHenryIE\MoneroRpc\Wallet;

final readonly class Key
{
    public function __construct(
        public string $key,
    ) {
    }
}
