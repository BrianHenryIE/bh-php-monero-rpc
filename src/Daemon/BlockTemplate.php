<?php

namespace BrianHenryIE\MoneroRpc\Daemon;

use BrianHenryIE\MoneroRpc\MoneroAmount;

/**
 *
 * {
 * "blockhashing_blob" : "0110d0d48aba063e419d5ea2f867d8acb8cba093eaf1a64a61ed5bb0cf1d98cdd8e486368e09c000000000d693e0f295e74425b32ed99be49095f50b986366437622a84a37609cd74456aa01",
 * "blocktemplate_blob" : "0110d0d48aba063e419d5ea2f867d8acb8cba093eaf1a64a61ed5bb0cf1d98cdd8e486368e09c00000000001d92d01ff9d2d06c587c09d010230f93069e9cae742d545c3312e739004f8da14428613a78ad0b7592d07ea5a8480e497d01202d4b5e43bb68563028d61bce7c83c2d1900f826840a33ce9992fc939dc7c6f5148088aca3cf0202bdc90f1fe033f18323249431c81c93985fe92afa24ef302478439dd8eb4fd03f80c0ee8ed20b026ecc536186c5e3d34562e87b4ef5f6212f65fcba1f42e1ecf9776bb44a99cbf580e08d84ddcb0102f56c0d2d8e70cf56890238f1df8e0343248bcd4247da62f5391f331a1281799f80c0caf384a3020251378e3a28b6acb8bf8c0f4231024b258319058612b951239492140aece6430f5f0115b532de951568105b13854f4b6616d8b6c696204b33912af7f24ac1a7d6deb4023c00000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000",
 * "difficulty" : 100,
 * "difficulty_top64" : 0,
 * "expected_reward" : 17495330302405,
 * "height" : 5789,
 * "next_seed_hash" : "",
 * "prev_hash" : "3e419d5ea2f867d8acb8cba093eaf1a64a61ed5bb0cf1d98cdd8e486368e09c0",
 * "reserved_offset" : 321,
 * "seed_hash" : "",
 * "seed_height" : 4096,
 * "status" : "OK",
 * "untrusted" : false,
 * "wide_difficulty" : "0x64"
 * }
 */
final readonly class BlockTemplate extends ResponseBase
{
    /**
     * @param string $wideDifficulty base16/hex string.
     * @param MoneroAmount $expectedReward Expected block reward in atomic units.
     */
    public function __construct(
        public string $blockhashingBlob,
        public string $blocktemplateBlob,
        public int $difficulty,
        public int $difficultyTop64,
        public MoneroAmount $expectedReward,
        public int $height,
        public string $nextSeedHash,
        public string $prevHash,
        public int $reservedOffset,
        public string $seedHash,
        public int $seedHeight,
        public string $wideDifficulty,
        string $status,
        bool $untrusted,
    ) {
        parent::__construct($status, $untrusted);
    }
}
