<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

use BrianHenryIE\MoneroRpc\MoneroAmount;

/**
 * A destination address + amount, as listed in a `describe_transfer` description.
 */
final readonly class Recipient
{
    public function __construct(
        public string $address,
        public MoneroAmount $amount,
    ) {
    }
}
