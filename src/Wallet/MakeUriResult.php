<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

/**
 * Response of the wallet `make_uri` RPC.
 */
final readonly class MakeUriResult
{
    public function __construct(
        public string $uri,
    ) {
    }
}
