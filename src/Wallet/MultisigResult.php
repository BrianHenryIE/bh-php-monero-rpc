<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

/**
 * Response of the wallet `make_multisig` and `exchange_multisig_keys` RPCs.
 *
 * `multisigInfo` is the data to pass to the next exchange round; it is empty once setup is
 * complete.
 */
final readonly class MultisigResult
{
    public function __construct(
        public string $address,
        public string $multisigInfo = '',
    ) {
    }
}
