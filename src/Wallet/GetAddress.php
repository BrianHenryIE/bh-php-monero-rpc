<?php

/**
 *
 */

namespace BrianHenryIE\MoneroRpc\Wallet;

interface GetAddress
{
    public function getAddress(): string;

    /**
     * @return Address[]
     */
    public function getAddresses(): array;
}
