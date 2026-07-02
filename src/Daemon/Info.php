<?php

namespace BrianHenryIE\MoneroRpc\Daemon;

final readonly class Info extends ResponseBase
{
    public function __construct(
        public int $adjustedTime,
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
        public string $nettype,
        public bool $offline,
        public int $outgoingConnectionsCount,
        public bool $restricted,
        public int $rpcConnectionsCount,
        public bool $stagenet,
        public int $startTime,
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
