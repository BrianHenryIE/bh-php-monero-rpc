<?php

namespace BrianHenryIE\MoneroRpc\Daemon;

/**
 * Response of the daemon `out_peers` RPC, echoing the configured maximum number of
 * outgoing peer connections.
 */
final readonly class OutPeers extends ResponseBase
{
    public function __construct(
        public int $outPeers,
        string $status,
        bool $untrusted,
    ) {
        parent::__construct($status, $untrusted);
    }
}
