<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

/**
 * A single opaque signature, as returned by get_tx_proof, get_spend_proof,
 * get_reserve_proof and sign.
 */
final readonly class Signature
{
    public function __construct(
        public string $signature,
    ) {
    }
}
