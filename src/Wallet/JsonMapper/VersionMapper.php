<?php


namespace BrianHenryIE\MoneroRpc\Wallet\JsonMapper;

use BrianHenryIE\MoneroRpc\Wallet\Version;

class VersionMapper implements Version {

	/** @var bool */
	protected bool $release;

	/** @var int */
	protected int $version;

	/**
	 * @return bool
	 */
	public function getRelease(): bool {
		return $this->release;
	}

	/**
	 * @return int
	 */
	public function getVersion(): int {
		return $this->version;
	}

	/**
	 * @param bool $release
	 */
	public function setRelease( bool $release ): void {
		$this->release = $release;
	}

	/**
	 * @param int $version
	 */
	public function setVersion( int $version ): void {
		$this->version = $version;
	}
}