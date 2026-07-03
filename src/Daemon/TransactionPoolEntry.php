<?php

namespace BrianHenryIE\MoneroRpc\Daemon;

use BrianHenryIE\MoneroRpc\MoneroAmount;
use DateTimeImmutable;

/**
 * A single pooled (unconfirmed) transaction, as returned in the `transactions` array of the
 * daemon `get_transaction_pool` RPC.
 */
final readonly class TransactionPoolEntry
{
    /**
     * @param string             $idHash            The transaction id.
     * @param MoneroAmount       $fee               Transaction fee in atomic units.
     * @param int                $blobSize          Size of the tx blob in bytes.
     * @param int                $weight            Transaction weight.
     * @param DateTimeImmutable  $receiveTime       When the daemon first received the tx (epoch → UTC).
     * @param ?DateTimeImmutable $lastRelayedTime   When the tx was last relayed (epoch → UTC); null if never.
     * @param bool               $relayed           Whether the tx has been relayed.
     * @param bool               $doNotRelay        Whether relaying is suppressed.
     * @param bool               $doubleSpendSeen   Whether a double-spend has been seen.
     * @param bool               $keptByBlock       Whether the tx came from a popped block.
     * @param int                $maxUsedBlockHeight Highest block height referenced by the tx's inputs.
     * @param string             $maxUsedBlockIdHash Hash of that block.
     * @param int                $lastFailedHeight  Height of the last failed validation (0 if none).
     * @param string             $lastFailedIdHash  Block hash of the last failed validation.
     * @param string             $txBlob            The raw tx blob (hex).
     * @param string             $txJson            The tx as a JSON string.
     */
    public function __construct(
        public string $idHash,
        public MoneroAmount $fee,
        public int $blobSize,
        public int $weight,
        public DateTimeImmutable $receiveTime,
        public ?DateTimeImmutable $lastRelayedTime,
        public bool $relayed,
        public bool $doNotRelay,
        public bool $doubleSpendSeen,
        public bool $keptByBlock,
        public int $maxUsedBlockHeight,
        public string $maxUsedBlockIdHash,
        public int $lastFailedHeight,
        public string $lastFailedIdHash,
        public string $txBlob,
        public string $txJson,
    ) {
    }
}
