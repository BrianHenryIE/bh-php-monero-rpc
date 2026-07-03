<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

/**
 * Response of the wallet `get_tx_notes` RPC.
 */
final readonly class TxNotes
{
    /**
     * @param string[] $notes The notes, in the order the transaction ids were requested.
     */
    public function __construct(
        public array $notes = [],
    ) {
    }
}
