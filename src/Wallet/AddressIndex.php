<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

/**
 * Response of the wallet `get_address_index` RPC.
 */
final readonly class AddressIndex
{
    public function __construct(
        public SubaddressIndex $index,
    ) {
    }
}
