<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

/**
 * Response of the wallet `get_account_tags` RPC.
 */
final readonly class AccountTags
{
    /**
     * @param AccountTag[] $accountTags The wallet's account tags.
     */
    public function __construct(
        public array $accountTags = [],
    ) {
    }
}
