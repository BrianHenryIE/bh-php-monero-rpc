<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

use BrianHenryIE\MoneroRpc\MoneroAmount;

/**
 * A single account within a wallet, as listed by `get_accounts`.
 */
final readonly class SubaddressAccount
{
    /**
     * @param int          $accountIndex    The account (major) index.
     * @param MoneroAmount $balance         Account balance, in atomic units.
     * @param MoneroAmount $unlockedBalance Spendable balance, in atomic units.
     * @param string       $baseAddress     The account's base (primary) address.
     * @param string       $label           The account label.
     * @param string       $tag             The account tag, if any.
     */
    public function __construct(
        public int $accountIndex,
        public MoneroAmount $balance,
        public MoneroAmount $unlockedBalance,
        public string $baseAddress,
        public string $label,
        public string $tag = '',
    ) {
    }
}
