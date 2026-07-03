<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

/**
 * Response of the wallet `is_multisig` RPC.
 */
final readonly class MultisigStatus
{
    /**
     * @param bool $multisig  Whether the wallet is multisig.
     * @param bool $ready     Whether multisig setup is complete (ready to transact).
     * @param int  $threshold The M in an M-of-N multisig (signatures required).
     * @param int  $total     The N in an M-of-N multisig (participants).
     */
    public function __construct(
        public bool $multisig,
        public bool $ready,
        public int $threshold,
        public int $total,
    ) {
    }
}
