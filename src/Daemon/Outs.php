<?php

namespace BrianHenryIE\MoneroRpc\Daemon;

/**
 * Response of the daemon `get_outs` RPC: the requested outputs' keys, masks and heights.
 */
final readonly class Outs extends ResponseBase
{
    /**
     * @param int          $credits RPC-payment credits (a count, NOT a Monero amount).
     * @param OutEntry[]    $outs    The requested outputs, in request order.
     * @param string       $topHash Hash of the highest block (RPC-payment bookkeeping).
     */
    public function __construct(
        public int $credits,
        public array $outs,
        public string $topHash,
        string $status,
        bool $untrusted,
    ) {
        parent::__construct($status, $untrusted);
    }
}
