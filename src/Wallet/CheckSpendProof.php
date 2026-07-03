<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

/**
 * Response of the wallet `check_spend_proof` RPC.
 */
final readonly class CheckSpendProof
{
    public function __construct(
        public bool $good,
    ) {
    }
}
