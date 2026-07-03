<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

/**
 * Response of the wallet `export_outputs` RPC.
 */
final readonly class OutputsData
{
    public function __construct(
        public string $outputsDataHex,
    ) {
    }
}
