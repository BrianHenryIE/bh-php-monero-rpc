<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

/**
 * Response of the wallet `get_attribute` RPC.
 */
final readonly class GetAttribute
{
    public function __construct(
        public string $value,
    ) {
    }
}
