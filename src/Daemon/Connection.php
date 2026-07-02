<?php

namespace BrianHenryIE\MoneroRpc\Daemon;

interface Connection
{
    public function getAddress(): string;

	/**
	 * invalid = 0,
	 * ipv4 = 1,
	 * ipv6 = 2,
	 * i2p = 3,
	 * tor = 4
	 *
	 * @see monero-project/monero/contrib/epee/include/net/enums.h
	 */
	public function getAddressType(): int;

    public function getAvgDownload(): int;


    public function getAvgUpload(): int;


    public function getConnectionId(): string;


    public function getCurrentDownload(): int;


    public function getCurrentUpload(): int;

    public function getHeight(): int;

    public function getHost(): string;


    public function getIncoming(): bool;


    public function getIp(): string;


    public function getLiveTime(): int;


    public function getLocalIp(): bool;


    public function getLocalhost(): bool;


    public function getPeerId(): string;


    public function getPort(): string;


    public function getPruningSeed(): int;


    public function getRecvCount(): int;


    public function getRecvIdleTime(): int;


    public function getRpcCreditsPerHash(): int;


    public function getRpcPort(): int;


    public function getSendCount(): int;


    public function getSendIdleTime(): int;


    public function getState(): string;


    public function getSupportFlags(): int;
}
