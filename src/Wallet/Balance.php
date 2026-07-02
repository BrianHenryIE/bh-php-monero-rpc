<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

final readonly class Balance
{
    public function __construct(
        public int $balance,
        public int $blocksToUnlock,
        public bool $multisigImportNeeded,
        public int $timeToUnlock,
        public int $unlockedBalance,
    ) {
    }
}
