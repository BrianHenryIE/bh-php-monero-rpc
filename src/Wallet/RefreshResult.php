<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

final readonly class RefreshResult
{
    public function __construct(
        public int $blocksFetched,
        public bool $receivedMoney,
    ) {
    }
}
