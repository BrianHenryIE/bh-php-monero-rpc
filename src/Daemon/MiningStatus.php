<?php

namespace BrianHenryIE\MoneroRpc\Daemon;

interface MiningStatus extends ResponseBase
{
    public function getActive(): bool;
    public function getAddress(): string;
    public function getBgIdleThreshold(): int;
    public function getBgIgnoreBattery(): bool;
    public function getBgMinIdleSeconds(): int;
    public function getBgTarget(): int;
    public function getBlockReward(): int;
    public function getBlockTarget(): int;

    public function getDifficulty(): int;
    public function getDifficultyTop64(): int;

    public function getIsBackgroundMiningEnabled(): bool;

	/**
	 * pow: proof of work
	 */
    public function getPowAlgorithm(): string;

    public function getSpeed(): int;


    public function getThreadsCount(): int;


    public function getWideDifficulty(): string;
}
