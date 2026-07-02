<?php

namespace BrianHenryIE\MoneroRpc\Wallet\JsonMapper;

use BrianHenryIE\MoneroRpc\Wallet\RestoreDeterministicWalletResult;

class RestoreDeterministicWalletResultMapper implements RestoreDeterministicWalletResult
{
    protected string $address;

    protected string $info;

    protected string $seed;

    protected bool $wasDeprecated;

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setAddress(string $address): void
    {
        $this->address = $address;
    }

    public function getInfo(): string
    {
        return $this->info;
    }

    public function setInfo(string $info): void
    {
        $this->info = $info;
    }

    public function getSeed(): string
    {
        return $this->seed;
    }

    public function setSeed(string $seed): void
    {
        $this->seed = $seed;
    }

    public function isWasDeprecated(): bool
    {
        return $this->wasDeprecated;
    }

    public function setWasDeprecated(bool $wasDeprecated): void
    {
        $this->wasDeprecated = $wasDeprecated;
    }
}
