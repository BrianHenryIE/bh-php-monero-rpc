<?php

/**
 *
 * To res`monero-wallet-cli --restore-deterministic-wallet` and enter your mnemonic seed.
 */

namespace BrianHenryIE\MoneroRpc\Wallet;

interface Key
{
    public function getKey(): string;
}
