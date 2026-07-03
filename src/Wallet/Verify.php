<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

/**
 * Response of the wallet `verify` RPC (verify a signed message).
 */
final readonly class Verify
{
    /**
     * @param bool   $good          Whether the signature is valid.
     * @param bool   $old           Whether it is an old-format (v1) signature.
     * @param string $signatureType The signature type, e.g. "spend" or "view".
     * @param int    $version       The signature version.
     */
    public function __construct(
        public bool $good,
        public bool $old,
        public string $signatureType,
        public int $version,
    ) {
    }
}
