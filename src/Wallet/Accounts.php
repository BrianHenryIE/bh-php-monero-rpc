<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

use BrianHenryIE\MoneroRpc\MoneroAmount;

/**
 * Response of the wallet `get_accounts` RPC.
 */
final readonly class Accounts
{
    /**
     * @param SubaddressAccount[] $subaddressAccounts   The wallet's accounts.
     * @param MoneroAmount        $totalBalance         Sum of all account balances, in atomic units.
     * @param MoneroAmount        $totalUnlockedBalance Sum of all spendable balances, in atomic units.
     */
    public function __construct(
        public array $subaddressAccounts,
        public MoneroAmount $totalBalance,
        public MoneroAmount $totalUnlockedBalance,
    ) {
    }
}
