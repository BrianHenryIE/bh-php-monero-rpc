<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

/**
 * A subaddress index: the account (major) index plus the subaddress (minor) index within it.
 */
final readonly class SubaddressIndex
{
    public function __construct(
        public int $major,
        public int $minor,
    ) {
    }
}
