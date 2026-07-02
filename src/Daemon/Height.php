<?php

namespace BrianHenryIE\MoneroRpc\Daemon;

final readonly class Height
{
    /**
     * @param ?string $hash Optional: the wallet RPC `get_height` returns only `height`; the daemon
     *                      RPC `get_height` also returns the top block `hash`.
     */
    public function __construct(
        public int $height,
        public ?string $hash = null,
    ) {
    }
}
