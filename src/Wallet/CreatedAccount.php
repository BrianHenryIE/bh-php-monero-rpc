<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

/**
 * Response of the wallet `create_account` RPC.
 */
final readonly class CreatedAccount
{
    public function __construct(
        public int $accountIndex,
        public string $address,
    ) {
    }
}
