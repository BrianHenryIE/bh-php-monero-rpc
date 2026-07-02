<?php

namespace BrianHenryIE\MoneroRpc\Wallet\JsonMapper;

use BrianHenryIE\MoneroRpc\Wallet\IntegratedAddress;

class IntegratedAddressMapper implements IntegratedAddress
{
    protected string $integratedAddress;
    protected string $paymentId;

    public function getIntegratedAddress(): string
    {
        return $this->integratedAddress;
    }

    public function setIntegratedAddress(string $integratedAddress): void
    {
        $this->integratedAddress = $integratedAddress;
    }

    public function getPaymentId(): string
    {
        return $this->paymentId;
    }

    public function setPaymentId(string $paymentId): void
    {
        $this->paymentId = $paymentId;
    }
}
