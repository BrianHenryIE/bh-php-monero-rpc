<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

/**
 * Response of the wallet `get_languages` RPC.
 */
final readonly class Languages
{
    /**
     * @param string[] $languages      Mnemonic language names (English endonyms).
     * @param string[] $languagesLocal Mnemonic language names in their own script.
     */
    public function __construct(
        public array $languages,
        public array $languagesLocal = [],
    ) {
    }
}
