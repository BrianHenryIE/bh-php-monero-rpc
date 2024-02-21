<?php

/**
 *
 */

namespace BrianHenryIE\MoneroRpc\Wallet;

interface Address
{
    public function getAddress(): string;

    public function getAddressIndex(): int;

    public function getLabel(): string;

    public function getUsed(): bool;
}
