<?php

namespace BrianHenryIE\MoneroRpc\Daemon;

/**
 * Response of the daemon `is_key_image_spent` RPC: the spent status of each queried key image,
 * in request order.
 */
final readonly class KeyImageSpent extends ResponseBase
{
    /**
     * @param int                   $credits     RPC-payment credits (a count, NOT a Monero amount).
     * @param KeyImageSpentStatus[] $spentStatus Per-image spent status, in the order requested.
     * @param string                $topHash     Hash of the highest block (RPC-payment bookkeeping).
     */
    public function __construct(
        public int $credits,
        public array $spentStatus,
        public string $topHash,
        string $status,
        bool $untrusted,
    ) {
        parent::__construct($status, $untrusted);
    }
}
