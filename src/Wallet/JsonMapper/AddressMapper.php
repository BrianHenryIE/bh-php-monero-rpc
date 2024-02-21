<?php

namespace BrianHenryIE\MoneroRpc\Wallet\JsonMapper;

use BrianHenryIE\MoneroRpc\Wallet\Address;

class AddressMapper implements Address
{
    public function __construct(
        protected string $address,
        protected int $addressIndex,
        protected string $label,
        protected bool $used
    ) {
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getAddressIndex(): int
    {
        return $this->addressIndex;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getUsed(): bool
    {
        return $this->used;
    }

    public function setAddress(string $address): void
    {
        $this->address = $address;
    }

    public function setAddressIndex(int $addressIndex): void
    {
        $this->addressIndex = $addressIndex;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    public function setUsed(bool $used): void
    {
        $this->used = $used;
    }
}
