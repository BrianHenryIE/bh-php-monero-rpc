<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

/**
 * Response of the wallet `describe_transfer` RPC.
 */
final readonly class DescribeTransferResult
{
    /**
     * @param TransferDescription[] $desc    One description per transaction in the set.
     * @param \stdClass             $summary Aggregate summary across all transactions (left unmapped).
     */
    public function __construct(
        public array $desc,
        public \stdClass $summary,
    ) {
    }
}
