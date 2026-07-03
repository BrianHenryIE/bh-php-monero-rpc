<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

/**
 * Response of the wallet `export_multisig_info` RPC.
 */
final readonly class MultisigInfoExport
{
    public function __construct(
        public string $info,
    ) {
    }
}
