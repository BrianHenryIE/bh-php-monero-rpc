<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

/**
 * Response of the wallet `get_payments` / `get_bulk_payments` RPCs. `payments` is omitted by
 * monerod when there are none, so it defaults to an empty array.
 */
final readonly class Payments
{
    /**
     * @param Payment[] $payments The matching payments.
     */
    public function __construct(
        public array $payments = [],
    ) {
    }
}
