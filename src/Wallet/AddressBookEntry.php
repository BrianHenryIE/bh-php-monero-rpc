<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

/**
 * A single entry in the wallet address book.
 */
final readonly class AddressBookEntry
{
    public function __construct(
        public int $index,
        public string $address,
        public string $description,
    ) {
    }
}
