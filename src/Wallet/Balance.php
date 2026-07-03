<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

use BrianHenryIE\MoneroRpc\MoneroAmount;

final readonly class Balance
{
    /**
     * @param MoneroAmount $balance Total balance in atomic units.
     * @param MoneroAmount $unlockedBalance Spendable (unlocked) balance in atomic units.
     * @param int $timeToUnlock Estimated seconds until funds unlock (a duration — not a timestamp).
     */
    public function __construct(
        public MoneroAmount $balance,
        public int $blocksToUnlock,
        public bool $multisigImportNeeded,
        public int $timeToUnlock,
        public MoneroAmount $unlockedBalance,
    ) {
    }
}
