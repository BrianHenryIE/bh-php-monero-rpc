<?php

namespace BrianHenryIE\MoneroRpc\Daemon;

final readonly class PeerList extends ResponseBase
{
    /**
     * @param PeerListEntry[] $grayList  5000. Optional: monerod omits `gray_list` when it is empty
     *                                   (observed live: a node whose only peers are white-listed).
     * @param PeerListEntry[] $whiteList Optional: monerod omits `white_list` when it is empty.
     */
    public function __construct(
        string $status,
        bool $untrusted,
        public array $grayList = [],
        public array $whiteList = [],
    ) {
        parent::__construct($status, $untrusted);
    }
}
