<?php

namespace BrianHenryIE\MoneroRpc\Daemon;

final readonly class BlockHeaderBy extends ResponseBase
{
    public function __construct(
        public BlockHeader $blockHeader,
        public int $credits,
        public string $topHash,
        string $status,
        bool $untrusted,
    ) {
        parent::__construct($status, $untrusted);
    }
}
