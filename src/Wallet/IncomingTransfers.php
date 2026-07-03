<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

/**
 * Response of the wallet `incoming_transfers` RPC. `transfers` is omitted by monerod when the
 * wallet has no matching outputs, so it defaults to an empty array.
 */
final readonly class IncomingTransfers
{
    /**
     * @param IncomingTransfer[] $transfers The matching outputs.
     */
    public function __construct(
        public array $transfers = [],
    ) {
    }
}
