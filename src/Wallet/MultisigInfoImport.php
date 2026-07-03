<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

/**
 * Response of the wallet `import_multisig_info` RPC.
 */
final readonly class MultisigInfoImport
{
    /**
     * @param int $nOutputs Number of outputs signed by the imported multisig info.
     */
    public function __construct(
        public int $nOutputs,
    ) {
    }
}
