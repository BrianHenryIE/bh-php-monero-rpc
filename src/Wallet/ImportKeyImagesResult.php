<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

use BrianHenryIE\MoneroRpc\MoneroAmount;

/**
 * Response of the wallet `import_key_images` RPC.
 */
final readonly class ImportKeyImagesResult
{
    /**
     * @param int          $height  Height up to which the images were imported.
     * @param MoneroAmount $spent   Total spent amount detected, in atomic units.
     * @param MoneroAmount $unspent Total unspent amount, in atomic units.
     */
    public function __construct(
        public int $height,
        public MoneroAmount $spent,
        public MoneroAmount $unspent,
    ) {
    }
}
