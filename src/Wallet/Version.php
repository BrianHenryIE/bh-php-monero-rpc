<?php

/**
 * `{"release":true,"version":65562}`
 */

namespace BrianHenryIE\MoneroRpc\Wallet;

interface Version
{
    public function getRelease(): bool;
    public function getVersion(): int;
}
