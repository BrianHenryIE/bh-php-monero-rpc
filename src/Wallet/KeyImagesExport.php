<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

/**
 * Response of the wallet `export_key_images` RPC.
 */
final readonly class KeyImagesExport
{
    /**
     * @param int              $offset          Index of the first exported output.
     * @param SignedKeyImage[] $signedKeyImages The signed key images.
     */
    public function __construct(
        public int $offset,
        public array $signedKeyImages = [],
    ) {
    }
}
