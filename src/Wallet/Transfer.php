<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

use BrianHenryIE\MoneroRpc\MoneroAmount;
use DateTimeImmutable;

/**
 * A single transfer as returned by the wallet `get_transfers` / `get_transfer_by_txid` RPCs.
 */
final readonly class Transfer
{
    /**
     * @param string             $txid          The transaction id.
     * @param MoneroAmount       $amount        Total amount of the transfer, in atomic units.
     * @param MoneroAmount       $fee           Fee paid, in atomic units.
     * @param TransferType       $type          Direction/category of the transfer.
     * @param int                $height        Block height (0 if unconfirmed).
     * @param ?DateTimeImmutable  $timestamp     Block time (epoch → UTC); null if unconfirmed (0).
     * @param bool               $doubleSpendSeen Whether a double-spend was seen.
     * @param bool               $locked        Whether the funds are still locked.
     * @param int                $confirmations Number of confirmations.
     * @param int                $unlockTime    Block HEIGHT or epoch (height when < 500000000) — raw int.
     * @param string             $address       Address involved in the transfer.
     * @param string             $paymentId     Payment id (may be all-zero).
     * @param string             $note          User note attached to the transfer.
     * @param SubaddressIndex    $subaddrIndex  Subaddress the transfer relates to.
     * @param MoneroAmount[]     $amounts       Per-output amounts, in atomic units.
     * @param SubaddressIndex[]  $subaddrIndices Subaddresses involved.
     * @param int                $suggestedConfirmationsThreshold Suggested confirmations to consider it safe.
     */
    public function __construct(
        public string $txid,
        public MoneroAmount $amount,
        public MoneroAmount $fee,
        public TransferType $type,
        public int $height,
        public ?DateTimeImmutable $timestamp,
        public bool $doubleSpendSeen,
        public bool $locked,
        public int $confirmations,
        public int $unlockTime,
        public string $address,
        public string $paymentId,
        public string $note,
        public SubaddressIndex $subaddrIndex,
        public array $amounts = [],
        public array $subaddrIndices = [],
        public int $suggestedConfirmationsThreshold = 0,
    ) {
    }
}
