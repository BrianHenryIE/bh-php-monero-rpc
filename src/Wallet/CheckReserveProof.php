<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

use BrianHenryIE\MoneroRpc\MoneroAmount;

/**
 * Response of the wallet `check_reserve_proof` RPC.
 */
final readonly class CheckReserveProof
{
    /**
     * @param bool         $good  Whether the proof is valid.
     * @param MoneroAmount $total Total amount the proof covers, in atomic units.
     * @param MoneroAmount $spent Amount of that already spent, in atomic units.
     */
    public function __construct(
        public bool $good,
        public MoneroAmount $total,
        public MoneroAmount $spent,
    ) {
    }
}
