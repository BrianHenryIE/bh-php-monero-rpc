<?php

namespace BrianHenryIE\MoneroRpc\Daemon;

final readonly class PeerListEntry
{
    /**
     * @param ?int $pruningSeed       Optional: monerod omits pruning fields for peers that do
     *                                not advertise them.
     * @param ?int $rpcPort           Optional: omitted for peers that do not advertise an RPC port.
     * @param ?int $rpcCreditsPerHash Optional: omitted for peers that do not advertise RPC credits.
     */
    public function __construct(
        public string $host,
        public int $id,
        public int $ip,
        public int $lastSeen,
        public int $port,
        public ?int $pruningSeed = null,
        public ?int $rpcPort = null,
        public ?int $rpcCreditsPerHash = null,
    ) {
    }
}
