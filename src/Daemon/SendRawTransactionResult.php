<?php

namespace BrianHenryIE\MoneroRpc\Daemon;

/**
 * Response of the daemon `send_raw_transaction` RPC.
 *
 * On rejection the boolean flags pinpoint why (`status` is "Failed" and, usually, `reason`
 * or one of the flags explains it); on success `status` is "OK".
 */
final readonly class SendRawTransactionResult extends ResponseBase
{
    /**
     * @param int    $credits           RPC-payment credits (a count, NOT a Monero amount).
     * @param bool   $doubleSpend       Input already spent.
     * @param bool   $feeTooLow         Fee below the required minimum.
     * @param bool   $invalidInput      An input is invalid.
     * @param bool   $invalidOutput     An output is invalid.
     * @param bool   $lowMixin          Ring size below the consensus minimum.
     * @param bool   $nonzeroUnlockTime Transaction has a non-zero unlock time (rejected in the pool).
     * @param bool   $notRelayed        The tx was accepted but not relayed.
     * @param bool   $overspend         Inputs do not cover outputs + fee.
     * @param string $reason            Human-readable rejection reason (often empty).
     * @param bool   $sanityCheckFailed A client-side sanity check failed.
     * @param bool   $tooBig            Transaction exceeds the size limit.
     * @param bool   $tooFewOutputs     Fewer outputs than required.
     * @param string $topHash           Hash of the highest block (RPC-payment bookkeeping).
     * @param bool   $txExtraTooBig     The tx_extra field is too large.
     */
    public function __construct(
        public int $credits,
        public bool $doubleSpend,
        public bool $feeTooLow,
        public bool $invalidInput,
        public bool $invalidOutput,
        public bool $lowMixin,
        public bool $nonzeroUnlockTime,
        public bool $notRelayed,
        public bool $overspend,
        public string $reason,
        public bool $sanityCheckFailed,
        public bool $tooBig,
        public bool $tooFewOutputs,
        public string $topHash,
        public bool $txExtraTooBig,
        string $status,
        bool $untrusted,
    ) {
        parent::__construct($status, $untrusted);
    }
}
