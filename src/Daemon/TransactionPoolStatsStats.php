<?php

namespace BrianHenryIE\MoneroRpc\Daemon;

final readonly class TransactionPoolStatsStats
{
    public function __construct(
        public int $bytesMax,
        public int $bytesMed,
        public int $bytesMin,
        public int $bytesTotal,
        public int $feeTotal,
        public int $histo98pc,
        public int $num10m,
        public int $numDoubleSpends,
        public int $numFailing,
        public int $numNotRelayed,
        public int $oldest,
        public int $txsTotal,
    ) {
    }
}
