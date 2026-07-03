<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

/**
 * A single account tag, as listed by `get_account_tags`.
 */
final readonly class AccountTag
{
    /**
     * @param string $tag      The tag name.
     * @param string $label    The tag label.
     * @param int[]  $accounts Indices of accounts carrying the tag.
     */
    public function __construct(
        public string $tag,
        public string $label,
        public array $accounts = [],
    ) {
    }
}
