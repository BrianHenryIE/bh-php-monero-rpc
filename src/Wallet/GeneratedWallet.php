<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

/**
 * Response of the wallet `generate_from_keys` RPC.
 */
final readonly class GeneratedWallet
{
    public function __construct(
        public string $address,
        public string $info,
    ) {
    }
}
