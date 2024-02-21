<?php

namespace BrianHenryIE\MoneroRpc\Daemon\JsonMapper;

use BrianHenryIE\MoneroRpc\Daemon\Connection;

class ConnectionMapper implements Connection
{
    protected string $address;
    protected int $addressType;
    protected int $avgDownload;
    protected int $avgUpload;
    protected string $connectionId;
    protected int $currentDownload;
    protected int $currentUpload;
    protected int $height;
    protected string $host;
    protected bool $incoming;
    protected string $ip;
    protected int $liveTime;
    protected bool $localIp;
    protected bool $localhost;
    protected string $peerId;
    protected string $port;
    protected int $pruningSeed;
    protected int $recvCount;
    protected int $recvIdleTime;
    protected int $rpcCreditsPerHash;
    protected int $rpcPort;
    protected int $sendCount;
    protected int $sendIdleTime;
    protected string $state;
    protected int $supportFlags;

    public function __construct(
        string $address,
        int $addressType,
        int $avgDownload,
        int $avgUpload,
        string $connectionId,
        int $currentDownload,
        int $currentUpload,
        int $height,
        string $host,
        bool $incoming,
        string $ip,
        int $liveTime,
        bool $localIp,
        bool $localhost,
        string $peerId,
        string $port,
        int $pruningSeed,
        int $recvCount,
        int $recvIdleTime,
        int $rpcCreditsPerHash,
        int $rpcPort,
        int $sendCount,
        int $sendIdleTime,
        string $state,
        int $supportFlags
    ) {
        $this->address = $address;
        $this->addressType = $addressType;
        $this->avgDownload = $avgDownload;
        $this->avgUpload = $avgUpload;
        $this->connectionId = $connectionId;
        $this->currentDownload = $currentDownload;
        $this->currentUpload = $currentUpload;
        $this->height = $height;
        $this->host = $host;
        $this->incoming = $incoming;
        $this->ip = $ip;
        $this->liveTime = $liveTime;
        $this->localIp = $localIp;
        $this->localhost = $localhost;
        $this->peerId = $peerId;
        $this->port = $port;
        $this->pruningSeed = $pruningSeed;
        $this->recvCount = $recvCount;
        $this->recvIdleTime = $recvIdleTime;
        $this->rpcCreditsPerHash = $rpcCreditsPerHash;
        $this->rpcPort = $rpcPort;
        $this->sendCount = $sendCount;
        $this->sendIdleTime = $sendIdleTime;
        $this->state = $state;
        $this->supportFlags = $supportFlags;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getAddressType(): int
    {
        return $this->addressType;
    }

    public function getAvgDownload(): int
    {
        return $this->avgDownload;
    }

    public function getAvgUpload(): int
    {
        return $this->avgUpload;
    }

    public function getConnectionId(): string
    {
        return $this->connectionId;
    }

    public function getCurrentDownload(): int
    {
        return $this->currentDownload;
    }

    public function getCurrentUpload(): int
    {
        return $this->currentUpload;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getIncoming(): bool
    {
        return $this->incoming;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function getLiveTime(): int
    {
        return $this->liveTime;
    }

    public function getLocalIp(): bool
    {
        return $this->localIp;
    }

    public function getLocalhost(): bool
    {
        return $this->localhost;
    }

    public function getPeerId(): string
    {
        return $this->peerId;
    }

    public function getPort(): string
    {
        return $this->port;
    }

    public function getPruningSeed(): int
    {
        return $this->pruningSeed;
    }

    public function getRecvCount(): int
    {
        return $this->recvCount;
    }

    public function getRecvIdleTime(): int
    {
        return $this->recvIdleTime;
    }

    public function getRpcCreditsPerHash(): int
    {
        return $this->rpcCreditsPerHash;
    }

    public function getRpcPort(): int
    {
        return $this->rpcPort;
    }

    public function getSendCount(): int
    {
        return $this->sendCount;
    }

    public function getSendIdleTime(): int
    {
        return $this->sendIdleTime;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getSupportFlags(): int
    {
        return $this->supportFlags;
    }

    public function setAddress(string $address): void
    {
        $this->address = $address;
    }

    public function setAddressType(int $addressType): void
    {
        $this->addressType = $addressType;
    }

    public function setAvgDownload(int $avgDownload): void
    {
        $this->avgDownload = $avgDownload;
    }

    public function setAvgUpload(int $avgUpload): void
    {
        $this->avgUpload = $avgUpload;
    }

    public function setConnectionId(string $connectionId): void
    {
        $this->connectionId = $connectionId;
    }

    public function setCurrentDownload(int $currentDownload): void
    {
        $this->currentDownload = $currentDownload;
    }

    public function setCurrentUpload(int $currentUpload): void
    {
        $this->currentUpload = $currentUpload;
    }

    public function setHeight(int $height): void
    {
        $this->height = $height;
    }

    public function setHost(string $host): void
    {
        $this->host = $host;
    }

    public function setIncoming(bool $incoming): void
    {
        $this->incoming = $incoming;
    }

    public function setIp(string $ip): void
    {
        $this->ip = $ip;
    }

    public function setLiveTime(int $liveTime): void
    {
        $this->liveTime = $liveTime;
    }

    public function setLocalIp(bool $localIp): void
    {
        $this->localIp = $localIp;
    }

    public function setLocalhost(bool $localhost): void
    {
        $this->localhost = $localhost;
    }

    public function setPeerId(string $peerId): void
    {
        $this->peerId = $peerId;
    }

    public function setPort(string $port): void
    {
        $this->port = $port;
    }

    public function setPruningSeed(int $pruningSeed): void
    {
        $this->pruningSeed = $pruningSeed;
    }

    public function setRecvCount(int $recvCount): void
    {
        $this->recvCount = $recvCount;
    }

    public function setRecvIdleTime(int $recvIdleTime): void
    {
        $this->recvIdleTime = $recvIdleTime;
    }

    public function setRpcCreditsPerHash(int $rpcCreditsPerHash): void
    {
        $this->rpcCreditsPerHash = $rpcCreditsPerHash;
    }

    public function setRpcPort(int $rpcPort): void
    {
        $this->rpcPort = $rpcPort;
    }

    public function setSendCount(int $sendCount): void
    {
        $this->sendCount = $sendCount;
    }

    public function setSendIdleTime(int $sendIdleTime): void
    {
        $this->sendIdleTime = $sendIdleTime;
    }

    public function setState(string $state): void
    {
        $this->state = $state;
    }

    public function setSupportFlags(int $supportFlags): void
    {
        $this->supportFlags = $supportFlags;
    }
}
