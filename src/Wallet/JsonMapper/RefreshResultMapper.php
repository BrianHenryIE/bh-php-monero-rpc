<?php

namespace BrianHenryIE\MoneroRpc\Wallet\JsonMapper;

use BrianHenryIE\MoneroRpc\Wallet\RefreshResult;

class RefreshResultMapper implements RefreshResult
{
    protected int $blocksFetched;

	protected bool $receivedMoney;

	public function getBlocksFetched(): int {
		return $this->blocksFetched;
	}

	public function setBlocksFetched( int $blocksFetched ): void {
		$this->blocksFetched = $blocksFetched;
	}

	public function isReceivedMoney(): bool {
		return $this->receivedMoney;
	}

	public function setReceivedMoney( bool $receivedMoney ): void {
		$this->receivedMoney = $receivedMoney;
	}
}
