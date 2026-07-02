<?php

namespace BrianHenryIE\MoneroRpc\Wallet\JsonMapper;

use BrianHenryIE\MoneroRpc\Wallet\Version;

class VersionMapper implements Version
{
    /** @var bool */
    protected bool $release;

    /** @var int */
    protected int $version;

    /**
     * @return bool
     */
    public function getRelease(): bool
    {
        return $this->release;
    }

    /**
     * @return int
     */
    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * @param bool $release
     */
    public function setRelease(bool $release): void
    {
        $this->release = $release;
    }

    /**
     * @param int $version
     */
    public function setVersion(int $version): void
    {
		// Get RPC version Major & Minor integer-format, where Major is the first 16 bits and Minor the last 16 bits.
//	    $version - 65535; // major
	    // 65563
	    // 000000010000000000011011
	    // 00000001 0000000000011011
	    // 1.27
        $this->version = $version;
    }
}
