<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

/**
 * Response of the wallet `validate_address` RPC.
 */
final readonly class AddressValidation
{
    /**
     * @param bool   $valid            Whether the address is valid.
     * @param bool   $integrated       Whether it is an integrated address.
     * @param bool   $subaddress       Whether it is a subaddress.
     * @param string $nettype          Network the address belongs to ("" when invalid).
     * @param string $openaliasAddress Resolved OpenAlias address, if any.
     */
    public function __construct(
        public bool $valid,
        public bool $integrated,
        public bool $subaddress,
        public string $nettype,
        public string $openaliasAddress,
    ) {
    }
}
