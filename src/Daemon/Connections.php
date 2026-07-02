<?php

namespace BrianHenryIE\MoneroRpc\Daemon;

final readonly class Connections extends ResponseBase
{
    /**
     * @param Connection[] $connections
     */
    public function __construct(
        public array $connections,
        string $status,
        bool $untrusted,
    ) {
        parent::__construct($status, $untrusted);
    }
}
