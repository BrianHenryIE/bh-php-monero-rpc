<?php

namespace BrianHenryIE\MoneroRpc\Wallet\JsonMapper;

use BrianHenryIE\MoneroRpc\Wallet\Balance;

class BalanceMapper implements Balance
{
    public function __construct(
        protected int $balance,
        protected int $blocksToUnlock,
        protected bool $multisigImportNeeded,
        protected int $timeToUnlock,
        protected int $unlockedBalance
    ) {
    }

    public function getBalance(): int
    {
        return $this->balance;
    }

    public function getBlocksToUnlock(): int
    {
        return $this->blocksToUnlock;
    }

    public function getMultisigImportNeeded(): bool
    {
        return $this->multisigImportNeeded;
    }

    public function getTimeToUnlock(): int
    {
        return $this->timeToUnlock;
    }

    public function getUnlockedBalance(): int
    {
        return $this->unlockedBalance;
    }

    public function setBalance(int $balance): void
    {
        $this->balance = $balance;
    }

    public function setBlocksToUnlock(int $blocksToUnlock): void
    {
        $this->blocksToUnlock = $blocksToUnlock;
    }

    public function setMultisigImportNeeded(bool $multisigImportNeeded): void
    {
        $this->multisigImportNeeded = $multisigImportNeeded;
    }

    public function setTimeToUnlock(int $timeToUnlock): void
    {
        $this->timeToUnlock = $timeToUnlock;
    }

    public function setUnlockedBalance(int $unlockedBalance): void
    {
        $this->unlockedBalance = $unlockedBalance;
    }
}
