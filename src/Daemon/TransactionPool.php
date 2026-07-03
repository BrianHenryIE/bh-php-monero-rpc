<?php

namespace BrianHenryIE\MoneroRpc\Daemon;

/**
 * Response of the daemon `get_transaction_pool` RPC: the full set of unconfirmed
 * transactions and the key images they spend.
 *
 * `transactions` and `spentKeyImages` are omitted by monerod when the pool is empty, so
 * both default to empty arrays.
 */
final readonly class TransactionPool extends ResponseBase
{
    /**
     * @param int                     $credits        RPC-payment credits (a count, NOT a Monero amount).
     * @param string                  $topHash        Hash of the highest block (RPC-payment bookkeeping).
     * @param TransactionPoolEntry[]  $transactions   The pooled transactions.
     * @param SpentKeyImage[]         $spentKeyImages Key images spent by pooled transactions.
     */
    public function __construct(
        public int $credits,
        public string $topHash,
        string $status,
        bool $untrusted,
        public array $transactions = [],
        public array $spentKeyImages = [],
    ) {
        parent::__construct($status, $untrusted);
    }
}
