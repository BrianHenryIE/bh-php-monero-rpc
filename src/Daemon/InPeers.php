<?php

namespace BrianHenryIE\MoneroRpc\Daemon;

final readonly class InPeers extends ResponseBase
{
    public function __construct(
        public int $inPeers,
        string $status,
        bool $untrusted,
    ) {
        parent::__construct($status, $untrusted);
    }
}
