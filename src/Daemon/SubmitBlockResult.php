<?php

namespace BrianHenryIE\MoneroRpc\Daemon;

/**
 * Response of the daemon `submitblock` RPC.
 *
 * A malformed blob is rejected with a JSON-RPC error ("Wrong block blob"), surfaced as an
 * exception rather than this model; on acceptance `status` is "OK" and `blockId` is the hash
 * of the accepted block.
 */
final readonly class SubmitBlockResult extends ResponseBase
{
    /**
     * @param string $blockId Hash of the accepted block (empty if not reported).
     */
    public function __construct(
        string $status,
        bool $untrusted,
        public string $blockId = '',
    ) {
        parent::__construct($status, $untrusted);
    }
}
