<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

final readonly class Address
{
    public function __construct(
        public string $address,
        public int $addressIndex,
        public string $label,
        public bool $used,
    ) {
    }
}
