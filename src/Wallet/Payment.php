<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

use BrianHenryIE\MoneroRpc\MoneroAmount;

/**
 * A single payment as returned by the wallet `get_payments` / `get_bulk_payments` RPCs.
 */
final readonly class Payment
{
    /**
     * @param string          $txHash       Transaction that made the payment.
     * @param string          $paymentId    The payment id (from the integrated address).
     * @param MoneroAmount    $amount       Amount received, in atomic units.
     * @param int             $blockHeight  Height the payment was received at.
     * @param bool            $locked       Whether the funds are still locked.
     * @param string          $address      The (sub)address that received the payment.
     * @param SubaddressIndex $subaddrIndex Subaddress that received the payment.
     * @param int             $unlockTime   Block HEIGHT or epoch (height when < 500000000) — raw int.
     */
    public function __construct(
        public string $txHash,
        public string $paymentId,
        public MoneroAmount $amount,
        public int $blockHeight,
        public bool $locked,
        public string $address,
        public SubaddressIndex $subaddrIndex,
        public int $unlockTime,
    ) {
    }
}
