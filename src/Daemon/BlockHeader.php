<?php

namespace BrianHenryIE\MoneroRpc\Daemon;

final readonly class BlockHeader
{
    public function __construct(
        public int $blockSize,
        public int $blockWeight,
        public int $cumulativeDifficulty,
        public int $cumulativeDifficultyTop64,
        public int $depth,
        public int $difficulty,
        public int $difficultyTop64,
        public string $hash,
        public int $height,
        public int $longTermWeight,
        public int $majorVersion,
        public string $minerTxHash,
        public int $minorVersion,
        public int $nonce,
        public int $numTxes,
        public bool $orphanStatus,
        public string $powHash,
        public string $prevHash,
        public int $reward,
        public int $timestamp,
        public string $wideCumulativeDifficulty,
        public string $wideDifficulty,
    ) {
    }
}
