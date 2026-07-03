<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

use BrianHenryIE\MoneroRpc\MoneroAmount;

/**
 * Response of the wallet `check_tx_key` RPC.
 */
final readonly class CheckTxKey
{
    /**
     * @param int          $confirmations Number of confirmations of the transaction.
     * @param bool         $inPool        Whether the transaction is still in the pool.
     * @param MoneroAmount $received      Amount the address received in the transaction.
     */
    public function __construct(
        public int $confirmations,
        public bool $inPool,
        public MoneroAmount $received,
    ) {
    }
}
