<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

final readonly class GetAddress
{
    /**
     * @param Address[] $addresses
     */
    public function __construct(
        public string $address,
        public array $addresses,
    ) {
    }
}
