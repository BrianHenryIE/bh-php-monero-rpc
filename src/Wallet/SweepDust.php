<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

final readonly class SweepDust
{
    public function __construct(
        public string $multisigTxset,
        public string $unsignedTxset,
    ) {
    }
}
