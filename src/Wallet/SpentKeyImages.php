<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

/**
 * The `spent_key_images` sub-object of a transfer/sweep response: the key images the
 * transaction spends.
 */
final readonly class SpentKeyImages
{
    /**
     * @param string[] $keyImages The spent key images.
     */
    public function __construct(
        public array $keyImages,
    ) {
    }
}
