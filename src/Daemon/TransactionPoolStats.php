<?php

namespace BrianHenryIE\MoneroRpc\Daemon;

final readonly class TransactionPoolStats extends ResponseBase
{
    public function __construct(
        public int $credits,
        public TransactionPoolStatsStats $poolStats,
        public string $topHash,
        string $status,
        bool $untrusted,
    ) {
        parent::__construct($status, $untrusted);
    }
}
