<?php

/**
 *
 */

namespace BrianHenryIE\MoneroRpc\Wallet;

interface Balance
{
    public function getBalance(): int;

    public function getBlocksToUnlock(): int;

    public function getMultisigImportNeeded(): bool;

    public function getTimeToUnlock(): int;

    public function getUnlockedBalance(): int;
}
