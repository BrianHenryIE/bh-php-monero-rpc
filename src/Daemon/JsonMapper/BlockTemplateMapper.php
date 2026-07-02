<?php

namespace BrianHenryIE\MoneroRpc\Daemon\JsonMapper;

use BrianHenryIE\MoneroRpc\Daemon\BlockTemplate;

class BlockTemplateMapper implements BlockTemplate
{
    use ResponseBaseTrait;

	protected string $blockhashingBlob;
	protected string $blocktemplateBlob;
	protected int $difficulty;
	protected int $difficultyTop64;
	protected int $expectedReward;
	protected int $height;
	protected string $nextSeedHash;
	protected string $prevHash;
	protected int $reservedOffset;
	protected string $seedHash;
	protected int $seedHeight;
	protected string $status;
	protected bool $untrusted;
	protected string $wideDifficulty;

	public function __construct(
		string $blockhashingBlob,
		string $blocktemplateBlob,
		int $difficulty,
		int $difficultyTop64,
		int $expectedReward,
		int $height,
		string $nextSeedHash,
		string $prevHash,
		int $reservedOffset,
		string $seedHash,
		int $seedHeight,
		string $status,
		bool $untrusted,
		string $wideDifficulty
	) {
		$this->blockhashingBlob = $blockhashingBlob;
		$this->blocktemplateBlob = $blocktemplateBlob;
		$this->difficulty = $difficulty;
		$this->difficultyTop64 = $difficultyTop64;
		$this->expectedReward = $expectedReward;
		$this->height = $height;
		$this->nextSeedHash = $nextSeedHash;
		$this->prevHash = $prevHash;
		$this->reservedOffset = $reservedOffset;
		$this->seedHash = $seedHash;
		$this->seedHeight = $seedHeight;
		$this->status = $status;
		$this->untrusted = $untrusted;
		$this->wideDifficulty = $wideDifficulty;
	}

	public function getBlockhashingBlob(): string
	{
		return $this->blockhashingBlob;
	}

	public function getBlocktemplateBlob(): string
	{
		return $this->blocktemplateBlob;
	}

	public function getDifficulty(): int
	{
		return $this->difficulty;
	}

	public function getDifficultyTop64(): int
	{
		return $this->difficultyTop64;
	}

	public function getExpectedReward(): int
	{
		return $this->expectedReward;
	}

	public function getHeight(): int
	{
		return $this->height;
	}

	public function getNextSeedHash(): string
	{
		return $this->nextSeedHash;
	}

	public function getPrevHash(): string
	{
		return $this->prevHash;
	}

	public function getReservedOffset(): int
	{
		return $this->reservedOffset;
	}

	public function getSeedHash(): string
	{
		return $this->seedHash;
	}

	public function getSeedHeight(): int
	{
		return $this->seedHeight;
	}

	public function getStatus(): string
	{
		return $this->status;
	}

	public function getUntrusted(): bool
	{
		return $this->untrusted;
	}

	public function getWideDifficulty(): string
	{
		return $this->wideDifficulty;
	}

	public function setBlockhashingBlob(string $blockhashingBlob): void
	{
		$this->blockhashingBlob = $blockhashingBlob;
	}

	public function setBlocktemplateBlob(string $blocktemplateBlob): void
	{
		$this->blocktemplateBlob = $blocktemplateBlob;
	}

	public function setDifficulty(int $difficulty): void
	{
		$this->difficulty = $difficulty;
	}

	public function setDifficultyTop64(int $difficultyTop64): void
	{
		$this->difficultyTop64 = $difficultyTop64;
	}

	public function setExpectedReward(int $expectedReward): void
	{
		$this->expectedReward = $expectedReward;
	}

	public function setHeight(int $height): void
	{
		$this->height = $height;
	}

	public function setNextSeedHash(string $nextSeedHash): void
	{
		$this->nextSeedHash = $nextSeedHash;
	}

	public function setPrevHash(string $prevHash): void
	{
		$this->prevHash = $prevHash;
	}

	public function setReservedOffset(int $reservedOffset): void
	{
		$this->reservedOffset = $reservedOffset;
	}

	public function setSeedHash(string $seedHash): void
	{
		$this->seedHash = $seedHash;
	}

	public function setSeedHeight(int $seedHeight): void
	{
		$this->seedHeight = $seedHeight;
	}

	public function setStatus(string $status): void
	{
		$this->status = $status;
	}

	public function setUntrusted(bool $untrusted): void
	{
		$this->untrusted = $untrusted;
	}

	public function setWideDifficulty(string $wideDifficulty): void
	{
		$this->wideDifficulty = $wideDifficulty;
	}
}
