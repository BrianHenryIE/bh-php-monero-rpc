<?php

namespace BrianHenryIE\MoneroRpc\Daemon;

use DateTimeImmutable;

final readonly class Info extends ResponseBase
{
    /**
     * @param ?DateTimeImmutable $adjustedTime Network-adjusted current time (epoch seconds → UTC);
     *                                         null if monerod reports 0 (its "unset" epoch sentinel).
     * @param ?DateTimeImmutable $startTime When this daemon process started (epoch seconds → UTC);
     *                                      null if monerod reports 0.
     * @param NetType $nettype The network this daemon is on.
     * @param int $credits RPC-payment credits (a count, NOT a Monero amount).
     * @param int $target Target block time, in SECONDS (a duration — not a timestamp).
     */
    public function __construct(
        public ?DateTimeImmutable $adjustedTime,
        public int $altBlocksCount,
        public int $blockSizeLimit,
        public int $blockSizeMedian,
        public int $blockWeightLimit,
        public int $blockWeightMedian,
        public string $bootstrapDaemonAddress,
        public bool $busySyncing,
        public int $credits,
        public int $cumulativeDifficulty,
        public int $cumulativeDifficultyTop64,
        public int $databaseSize,
        public int $difficulty,
        public int $difficultyTop64,
        public int $freeSpace,
        public int $greyPeerlistSize,
        public int $height,
        public int $heightWithoutBootstrap,
        public int $incomingConnectionsCount,
        public bool $mainnet,
        public NetType $nettype,
        public bool $offline,
        public int $outgoingConnectionsCount,
        public bool $restricted,
        public int $rpcConnectionsCount,
        public bool $stagenet,
        public ?DateTimeImmutable $startTime,
        public bool $synchronized,
        public int $target,
        public int $targetHeight,
        public bool $testnet,
        public string $topBlockHash,
        public string $topHash,
        public int $txCount,
        public int $txPoolSize,
        public bool $updateAvailable,
        public string $version,
        public bool $wasBootstrapEverUsed,
        public int $whitePeerlistSize,
        public string $wideCumulativeDifficulty,
        public string $wideDifficulty,
        string $status,
        bool $untrusted,
    ) {
        parent::__construct($status, $untrusted);
    }
}
