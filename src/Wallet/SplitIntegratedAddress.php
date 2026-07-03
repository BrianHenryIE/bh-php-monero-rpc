<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

/**
 * Response of the wallet `split_integrated_address` RPC.
 */
final readonly class SplitIntegratedAddress
{
    public function __construct(
        public bool $isSubaddress,
        public string $paymentId,
        public string $standardAddress,
    ) {
    }
}
