<?php

namespace BrianHenryIE\MoneroRpc\Daemon;

use DateTimeImmutable;

/**
 * A single transaction as returned in the `txs` array of the daemon `get_transactions` RPC.
 *
 * Several fields are conditional on request flags or on whether the transaction is confirmed
 * vs. still in the pool, so they carry defaults: `asJson` is present only when the request set
 * `decode_as_json`; a pooled (unconfirmed) transaction reports `blockHeight = 0`,
 * `blockTimestamp = null` and `confirmations = 0`.
 */
final readonly class TransactionEntry
{
    /**
     * @param string             $txHash         The transaction id.
     * @param int                $blockHeight    Height of the block containing it, or 0 if still in the pool.
     * @param ?DateTimeImmutable  $blockTimestamp Block time (epoch seconds → UTC); null when in the pool.
     * @param bool               $doubleSpendSeen Whether a double-spend of this tx has been seen.
     * @param bool               $inPool         Whether the tx is currently in the mempool.
     * @param int[]              $outputIndices  Global output indices of this tx's outputs.
     * @param int                $confirmations  Number of confirmations (0 while in the pool).
     * @param string             $asHex          Full tx as hex; present when requested.
     * @param string             $asJson         Full tx as a JSON string; present only with decode_as_json.
     * @param string             $prunableAsHex  Prunable part as hex.
     * @param string             $prunableHash   Hash of the prunable part.
     * @param string             $prunedAsHex    Pruned tx as hex.
     */
    public function __construct(
        public string $txHash,
        public int $blockHeight,
        public ?DateTimeImmutable $blockTimestamp,
        public bool $doubleSpendSeen,
        public bool $inPool,
        public array $outputIndices,
        public int $confirmations = 0,
        public string $asHex = '',
        public string $asJson = '',
        public string $prunableAsHex = '',
        public string $prunableHash = '',
        public string $prunedAsHex = '',
    ) {
    }
}
