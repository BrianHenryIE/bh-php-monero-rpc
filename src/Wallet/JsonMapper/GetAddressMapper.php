<?php

namespace BrianHenryIE\MoneroRpc\Wallet\JsonMapper;

use BrianHenryIE\MoneroRpc\Wallet\Address;
use BrianHenryIE\MoneroRpc\Wallet\GetAddress;

class GetAddressMapper implements GetAddress
{
    /**
     * @param AddressMapper[] $addresses
     */
    public function __construct(
        protected string $address,
        protected array $addresses
    ) {
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * @return Address[]
     */
    public function getAddresses(): array
    {
        return $this->addresses;
    }

    /**
     * @param string $address
     */
    public function setAddress(string $address): void
    {
        $this->address = $address;
    }

    /**
     * @param Address[] $addresses
     */
    public function setAddresses(array $addresses): void
    {
        $this->addresses = $addresses;
    }
}
