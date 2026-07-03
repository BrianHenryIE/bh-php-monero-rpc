<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

/**
 * Response of the wallet `create_address` RPC.
 */
final readonly class CreatedAddress
{
    /**
     * @param string   $address        The new subaddress.
     * @param int      $addressIndex   The new subaddress's (minor) index.
     * @param int[]    $addressIndices Indices of all created subaddresses (when count > 1).
     * @param string[] $addresses      All created subaddresses (when count > 1).
     */
    public function __construct(
        public string $address,
        public int $addressIndex,
        public array $addressIndices = [],
        public array $addresses = [],
    ) {
    }
}
