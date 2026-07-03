<?php

namespace BrianHenryIE\MoneroRpc\Daemon;

/**
 * Response of the daemon `get_transactions` RPC.
 */
final readonly class Transactions extends ResponseBase
{
    /**
     * @param int                 $credits   RPC-payment credits (a count, NOT a Monero amount).
     * @param TransactionEntry[]  $txs       The found transactions, in request order.
     * @param string              $topHash   Hash of the highest block (RPC-payment bookkeeping).
     * @param string[]            $txsAsHex  Each found tx as hex (parallel to $txs).
     * @param string[]            $txsAsJson Each found tx as a JSON string; only with decode_as_json.
     * @param string[]            $missedTx  Requested tx ids that were not found.
     */
    public function __construct(
        public int $credits,
        public array $txs,
        public string $topHash,
        string $status,
        bool $untrusted,
        public array $txsAsHex = [],
        public array $txsAsJson = [],
        public array $missedTx = [],
    ) {
        parent::__construct($status, $untrusted);
    }
}
