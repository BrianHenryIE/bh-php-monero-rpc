<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

final readonly class Refresh
{
    public function __construct(
        public RefreshResult $refreshResult,
    ) {
    }
}
