<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

/**
 * Response of the wallet `import_outputs` RPC.
 */
final readonly class ImportOutputsResult
{
    public function __construct(
        public int $numImported,
    ) {
    }
}
