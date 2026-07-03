<?php

namespace BrianHenryIE\MoneroRpc\Daemon;

/**
 * A key image spent by one or more pooled transactions, as listed in the
 * `spent_key_images` array of the daemon `get_transaction_pool` RPC.
 */
final readonly class SpentKeyImage
{
    /**
     * @param string   $idHash    The key image.
     * @param string[] $txsHashes Ids of the pooled transactions that spend it.
     */
    public function __construct(
        public string $idHash,
        public array $txsHashes,
    ) {
    }
}
