<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

use BrianHenryIE\MoneroRpc\MoneroAmount;

/**
 * Response of the wallet `check_tx_proof` RPC.
 */
final readonly class CheckTxProof
{
    /**
     * @param bool         $good          Whether the proof is valid.
     * @param int          $confirmations Number of confirmations.
     * @param bool         $inPool        Whether the transaction is still in the pool.
     * @param MoneroAmount $received      Amount the address received.
     */
    public function __construct(
        public bool $good,
        public int $confirmations,
        public bool $inPool,
        public MoneroAmount $received,
    ) {
    }
}
