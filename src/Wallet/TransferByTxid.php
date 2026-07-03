<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

/**
 * Response of the wallet `get_transfer_by_txid` RPC.
 *
 * `transfer` is the aggregate view of the transaction. `transfers` is the per-subaddress
 * breakdown (a transaction can touch several subaddresses of the account).
 */
final readonly class TransferByTxid
{
    /**
     * `$transfers` is intentionally left as raw {@see \stdClass} objects rather than
     * `Transfer[]`: the pinned json-mapper mis-hydrates a class that has BOTH a single
     * `Transfer` property and a `Transfer[]` property (a caching collision on the shared
     * element type). The aggregate `$transfer` is the primary, fully-typed value; the
     * per-subaddress breakdown is rarely needed and is exposed unmapped.
     *
     * @param Transfer      $transfer  The aggregate transfer.
     * @param \stdClass[]   $transfers Per-subaddress component transfers, unmapped.
     */
    public function __construct(
        public Transfer $transfer,
        public array $transfers,
    ) {
    }
}
