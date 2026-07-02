<?php

/**
 * `{"release":true,"version":65562}`
 */

namespace BrianHenryIE\MoneroRpc\Wallet;

final readonly class Version
{
    public function __construct(
        public bool $release,
        public int $version,
    ) {
    }
}
