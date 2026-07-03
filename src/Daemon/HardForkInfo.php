<?php

namespace BrianHenryIE\MoneroRpc\Daemon;

/**
 * Hard-fork status, as returned by the daemon `hard_fork_info` RPC.
 *
 * @see https://www.getmonero.org/resources/developer-guides/daemon-rpc.html#hard_fork_info
 */
final readonly class HardForkInfo extends ResponseBase
{
    /**
     * @param int    $credits        RPC-payment credits (a count, NOT a Monero amount).
     * @param int    $earliestHeight Block height at which the queried fork version becomes active.
     * @param bool   $enabled        Whether the hard fork is enabled.
     * @param int    $state          Fork readiness state (0 = likely-forthcoming, 1 = update-needed, 2 = ready).
     * @param int    $threshold      Voting threshold required to activate.
     * @param string $topHash        Hash of the highest block (RPC-payment bookkeeping).
     * @param int    $version        The active hard-fork version.
     * @param int    $votes          Number of votes for the new version within the window.
     * @param int    $voting         Version this daemon is voting for.
     * @param int    $window         Size (in blocks) of the voting window.
     */
    public function __construct(
        public int $credits,
        public int $earliestHeight,
        public bool $enabled,
        public int $state,
        public int $threshold,
        public string $topHash,
        public int $version,
        public int $votes,
        public int $voting,
        public int $window,
        string $status,
        bool $untrusted,
    ) {
        parent::__construct($status, $untrusted);
    }
}
