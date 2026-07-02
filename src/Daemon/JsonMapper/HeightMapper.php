<?php

namespace BrianHenryIE\MoneroRpc\Daemon\JsonMapper;

use BrianHenryIE\MoneroRpc\Daemon\Height;

class HeightMapper implements Height
{
    use ResponseBaseTrait;

    protected string $hash;

    protected int $height;

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

	public function setHash( string $hash ): void {
		$this->hash = $hash;
	}

	public function setHeight( int $height ): void {
		$this->height = $height;
	}


}
