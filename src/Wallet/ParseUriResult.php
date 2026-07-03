<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

/**
 * Response of the wallet `parse_uri` RPC.
 */
final readonly class ParseUriResult
{
    public function __construct(
        public ParsedUri $uri,
    ) {
    }
}
