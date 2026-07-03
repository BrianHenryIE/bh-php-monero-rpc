<?php

namespace BrianHenryIE\MoneroRpc\Daemon;

use BrianHenryIE\MoneroRpc\MoneroAmount;
use DateTimeImmutable;

final readonly class BlockHeader
{
    /**
     * @param MoneroAmount $reward Block reward in atomic units (was `int`; see MoneroAmount for why int is unsafe).
     * @param ?DateTimeImmutable $timestamp Block time (epoch seconds → UTC); null when monerod reports 0
     *                                      (its "unset" epoch sentinel — e.g. the genesis block).
     */
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
        public MoneroAmount $reward,
        public ?DateTimeImmutable $timestamp,
        public string $wideCumulativeDifficulty,
        public string $wideDifficulty,
    ) {
    }
}
