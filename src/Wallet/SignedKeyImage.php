<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

/**
 * A signed key image, as listed by `export_key_images`.
 */
final readonly class SignedKeyImage
{
    public function __construct(
        public string $keyImage,
        public string $signature,
    ) {
    }
}
