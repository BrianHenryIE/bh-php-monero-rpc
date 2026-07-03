<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

use BrianHenryIE\MoneroRpc\MoneroAmount;

/**
 * A single transaction's description within a `describe_transfer` response.
 */
final readonly class TransferDescription
{
    /**
     * @param MoneroAmount  $amountIn      Total input amount, in atomic units.
     * @param MoneroAmount  $amountOut     Total output amount, in atomic units.
     * @param MoneroAmount  $fee           Fee, in atomic units.
     * @param MoneroAmount  $changeAmount  Change returned to the wallet, in atomic units.
     * @param string        $changeAddress Address the change is sent to.
     * @param Recipient[]   $recipients    The destinations.
     * @param int           $ringSize      Ring size used.
     * @param int           $unlockTime    Block HEIGHT or epoch (height when < 500000000) — raw int.
     * @param int           $dummyOutputs  Number of dummy outputs.
     * @param string        $paymentId     Payment id (may be all-zero).
     * @param string        $extra         The tx_extra field, hex.
     * @param \stdClass[]   $sources       Input sources; left unmapped (deeply nested).
     */
    public function __construct(
        public MoneroAmount $amountIn,
        public MoneroAmount $amountOut,
        public MoneroAmount $fee,
        public MoneroAmount $changeAmount,
        public string $changeAddress,
        public array $recipients,
        public int $ringSize,
        public int $unlockTime,
        public int $dummyOutputs,
        public string $paymentId,
        public string $extra,
        public array $sources = [],
    ) {
    }
}
