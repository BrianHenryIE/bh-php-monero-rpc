<?php

namespace BrianHenryIE\MoneroRpc\Daemon;

use BrianHenryIE\MoneroRpc\MoneroAmount;

final readonly class MiningStatus extends ResponseBase
{
    /**
     * @param string $powAlgorithm pow: proof of work
     * @param MoneroAmount $blockReward Block reward in atomic units.
     * @param int $blockTarget Target block time, in SECONDS (a duration — not a timestamp).
     * @param int $speed Mining hashrate, in HASHES PER SECOND (not a currency amount).
     */
    public function __construct(
        public bool $active,
        public string $address,
        public int $bgIdleThreshold,
        public bool $bgIgnoreBattery,
        public int $bgMinIdleSeconds,
        public int $bgTarget,
        public MoneroAmount $blockReward,
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
