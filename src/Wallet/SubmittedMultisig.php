<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

/**
 * Response of the wallet `submit_multisig` RPC.
 */
final readonly class SubmittedMultisig
{
    /**
     * @param string[] $txHashList Ids of the submitted transactions.
     */
    public function __construct(
        public array $txHashList = [],
    ) {
    }
}
