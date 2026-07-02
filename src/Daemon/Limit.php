<?php

namespace BrianHenryIE\MoneroRpc\Daemon;

final readonly class Limit
{
    public function __construct(
        public int $limitDown,
        public int $limitUp,
    ) {
    }
}
