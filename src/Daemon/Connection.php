<?php

namespace BrianHenryIE\MoneroRpc\Daemon;

final readonly class Connection
{
    /**
     * @param int $addressType invalid = 0, ipv4 = 1, ipv6 = 2, i2p = 3, tor = 4
     *                         @see monero-project/monero/contrib/epee/include/net/enums.h
     */
    public function __construct(
        public string $address,
        public int $addressType,
        public int $avgDownload,
        public int $avgUpload,
        public string $connectionId,
        public int $currentDownload,
        public int $currentUpload,
        public int $height,
        public string $host,
        public bool $incoming,
        public string $ip,
        public int $liveTime,
        public bool $localIp,
        public bool $localhost,
        public string $peerId,
        public string $port,
        public int $pruningSeed,
        public int $recvCount,
        public int $recvIdleTime,
        public int $rpcCreditsPerHash,
        public int $rpcPort,
        public int $sendCount,
        public int $sendIdleTime,
        public string $state,
        public int $supportFlags,
    ) {
    }
}
