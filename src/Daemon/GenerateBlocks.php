<?php

namespace BrianHenryIE\MoneroRpc\Daemon;

final readonly class GenerateBlocks extends ResponseBase
{
    /**
     * @param string[] $blocks
     */
    public function __construct(
        public array $blocks,
        public int $height,
        string $status,
        bool $untrusted,
    ) {
        parent::__construct($status, $untrusted);
    }
}
