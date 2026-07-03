<?php

namespace BrianHenryIE\MoneroRpc\Daemon;

use BrianHenryIE\MoneroRpc\MoneroAmount;
use DateTimeImmutable;

final readonly class TransactionPoolStatsStats
{
    /**
     * @param MoneroAmount $feeTotal Total fees of all pooled transactions, in atomic units.
     * @param ?DateTimeImmutable $oldest Time the oldest pooled transaction entered (epoch seconds
     *                                   → UTC), or null when the pool is empty (monerod sends 0).
     */
    public function __construct(
        public int $bytesMax,
        public int $bytesMed,
        public int $bytesMin,
        public int $bytesTotal,
        public MoneroAmount $feeTotal,
        public int $histo98pc,
        public int $num10m,
        public int $numDoubleSpends,
        public int $numFailing,
        public int $numNotRelayed,
        public ?DateTimeImmutable $oldest,
        public int $txsTotal,
    ) {
    }
}
