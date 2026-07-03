<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

/**
 * Response of the wallet `relay_tx` RPC.
 */
final readonly class RelayTxResult
{
    /**
     * @param string $txHash The id of the relayed transaction.
     */
    public function __construct(
        public string $txHash,
    ) {
    }
}
