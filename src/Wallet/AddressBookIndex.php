<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

/**
 * Response of the wallet `add_address_book` RPC: the index of the new entry.
 */
final readonly class AddressBookIndex
{
    public function __construct(
        public int $index,
    ) {
    }
}
