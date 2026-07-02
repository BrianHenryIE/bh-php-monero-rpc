<?php

namespace BrianHenryIE\MoneroRpc\Daemon;

final readonly class MiningStatus extends ResponseBase
{
    /**
     * @param string $powAlgorithm pow: proof of work
     */
    public function __construct(
        public bool $active,
        public string $address,
        public int $bgIdleThreshold,
        public bool $bgIgnoreBattery,
        public int $bgMinIdleSeconds,
        public int $bgTarget,
        public int $blockReward,
        public int $blockTarget,
        public int $difficulty,
        public int $difficultyTop64,
        public bool $isBackgroundMiningEnabled,
        public string $powAlgorithm,
        public int $speed,
        public int $threadsCount,
        public string $wideDifficulty,
        string $status,
        bool $untrusted,
    ) {
        parent::__construct($status, $untrusted);
    }
}
