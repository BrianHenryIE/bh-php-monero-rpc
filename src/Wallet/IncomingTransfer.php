<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

use BrianHenryIE\MoneroRpc\MoneroAmount;

/**
 * A single output as returned by the wallet `incoming_transfers` RPC.
 */
final readonly class IncomingTransfer
{
    /**
     * @param MoneroAmount    $amount      Value of the output, in atomic units.
     * @param string          $txHash      Transaction the output belongs to.
     * @param int             $blockHeight Height the output was received at.
     * @param int             $globalIndex Global output index.
     * @param string          $keyImage    The output's key image.
     * @param string          $pubkey      The output's public key.
     * @param bool            $spent       Whether the output has been spent.
     * @param bool            $frozen      Whether the output is frozen.
     * @param bool            $unlocked    Whether the output is spendable.
     * @param SubaddressIndex $subaddrIndex Subaddress that owns the output.
     */
    public function __construct(
        public MoneroAmount $amount,
        public string $txHash,
        public int $blockHeight,
        public int $globalIndex,
        public string $keyImage,
        public string $pubkey,
        public bool $spent,
        public bool $frozen,
        public bool $unlocked,
        public SubaddressIndex $subaddrIndex,
    ) {
    }
}
