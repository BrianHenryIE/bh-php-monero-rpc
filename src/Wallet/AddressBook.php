<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

/**
 * Response of the wallet `get_address_book` RPC.
 */
final readonly class AddressBook
{
    /**
     * @param AddressBookEntry[] $entries The requested address-book entries.
     */
    public function __construct(
        public array $entries = [],
    ) {
    }
}
