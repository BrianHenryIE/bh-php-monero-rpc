<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

/**
 * Response of the wallet `get_transfers` RPC. Each category is present only when it was
 * requested and contains at least one transfer, so all default to empty arrays.
 */
final readonly class Transfers
{
    /**
     * @param Transfer[] $in      Confirmed incoming transfers.
     * @param Transfer[] $out     Confirmed outgoing transfers.
     * @param Transfer[] $pending Unconfirmed outgoing transfers.
     * @param Transfer[] $failed  Failed transfers.
     * @param Transfer[] $pool    Unconfirmed incoming (mempool) transfers.
     */
    public function __construct(
        public array $in = [],
        public array $out = [],
        public array $pending = [],
        public array $failed = [],
        public array $pool = [],
    ) {
    }
}
