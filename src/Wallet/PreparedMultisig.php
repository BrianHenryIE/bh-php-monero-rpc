<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

/**
 * Response of the wallet `prepare_multisig` RPC.
 */
final readonly class PreparedMultisig
{
    public function __construct(
        public string $multisigInfo,
    ) {
    }
}
