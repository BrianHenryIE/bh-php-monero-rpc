<?php

/**
 *
 */

namespace BrianHenryIE\MoneroRpc\Wallet;

interface RefreshResult
{
    public function getBlocksFetched(): int;

	public function isReceivedMoney(): bool;
}
