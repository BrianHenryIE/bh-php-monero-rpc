<?php

/**
 * @see https://www.getmonero.org/resources/developer-guides/daemon-rpc.html#get_block_count
 */

namespace BrianHenryIE\MoneroRpc\Daemon;

final readonly class BlockCount extends ResponseBase
{
    /**
     * @param int $count Number of blocks in the longest chain seen by the node.
     *                   @see https://github.com/monero-project/monero/blob/8123d945f874548e87d3850b6633f047120ece45/src/blockchain_db/blockchain_db.h#L1156-L1163
     *                   @see https://github.com/monero-project/monero/blob/8123d945f874548e87d3850b6633f047120ece45/src/blockchain_db/lmdb/db_lmdb.cpp#L2916-L2928
     */
    public function __construct(
        public int $count,
        string $status,
        bool $untrusted,
    ) {
        parent::__construct($status, $untrusted);
    }
}
