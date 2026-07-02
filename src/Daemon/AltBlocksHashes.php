<?php

namespace BrianHenryIE\MoneroRpc\Daemon;

final readonly class AltBlocksHashes extends ResponseBase
{
    public function __construct(
        public int $credits,
        public string $topHash,
        string $status,
        bool $untrusted,
    ) {
        parent::__construct($status, $untrusted);
    }
}
