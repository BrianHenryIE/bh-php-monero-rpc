<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

use BrianHenryIE\MoneroRpc\MoneroAmount;

/**
 * The `amounts_by_dest` sub-object of a transfer response: the amount sent to each
 * destination, in destination order.
 */
final readonly class AmountsByDest
{
    /**
     * @param MoneroAmount[] $amounts Amount sent to each destination, in atomic units.
     */
    public function __construct(
        public array $amounts,
    ) {
    }
}
