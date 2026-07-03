<?php

namespace BrianHenryIE\MoneroRpc\Daemon;

/**
 * A single output as returned in the `outs` array of the daemon `get_outs` RPC.
 */
final readonly class OutEntry
{
    /**
     * @param int    $height   Block height the output was mined in.
     * @param string $key      The output's one-time public key.
     * @param string $mask     The RingCT commitment mask.
     * @param bool   $unlocked Whether the output is currently spendable.
     * @param string $txid     Transaction id the output belongs to; only present when the request
     *                         set `get_txid = true`, so it defaults to an empty string.
     */
    public function __construct(
        public int $height,
        public string $key,
        public string $mask,
        public bool $unlocked,
        public string $txid = '',
    ) {
    }
}
