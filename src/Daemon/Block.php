<?php

namespace BrianHenryIE\MoneroRpc\Daemon;

final readonly class Block extends ResponseBase
{
    /**
     * @param string[] $txHashes Optional: monerod omits `tx_hashes` for blocks that contain only
     *                           the coinbase transaction (no user transactions), so absence means
     *                           an empty list.
     */
    public function __construct(
        public string $blob,
        public BlockHeader $blockHeader,
        public int $credits,
        public string $json,
        public string $minerTxHash,
        public string $topHash,
        string $status,
        bool $untrusted,
        public array $txHashes = [],
    ) {
        parent::__construct($status, $untrusted);
    }
}
