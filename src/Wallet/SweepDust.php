<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

interface SweepDust
{
    public function getMultisigTxset(): string;

    public function getUnsignedTxset(): string;
}
