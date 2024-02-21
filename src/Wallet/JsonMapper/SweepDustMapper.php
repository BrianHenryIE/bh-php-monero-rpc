<?php

namespace BrianHenryIE\MoneroRpc\Wallet\JsonMapper;

class SweepDustMapper
{
    public function __construct(protected string $multisigTxset, protected string $unsignedTxset)
    {
    }

    public function getMultisigTxset(): string
    {
        return $this->multisigTxset;
    }

    public function getUnsignedTxset(): string
    {
        return $this->unsignedTxset;
    }

    public function setMultisigTxset(string $multisigTxset): void
    {
        $this->multisigTxset = $multisigTxset;
    }

    public function setUnsignedTxset(string $unsignedTxset): void
    {
        $this->unsignedTxset = $unsignedTxset;
    }
}
