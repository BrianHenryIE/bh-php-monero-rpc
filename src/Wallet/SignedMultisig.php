<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

/**
 * Response of the wallet `sign_multisig` RPC.
 */
final readonly class SignedMultisig
{
    /**
     * @param string   $txDataHex  The signed multisig transaction data.
     * @param string[] $txHashList Ids of the fully-signed transactions (present once complete).
     */
    public function __construct(
        public string $txDataHex,
        public array $txHashList = [],
    ) {
    }
}
