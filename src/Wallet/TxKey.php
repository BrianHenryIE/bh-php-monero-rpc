<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

/**
 * Response of the wallet `get_tx_key` RPC.
 */
final readonly class TxKey
{
    public function __construct(
        public string $txKey,
    ) {
    }
}
