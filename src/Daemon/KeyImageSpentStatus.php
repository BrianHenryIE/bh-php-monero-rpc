<?php

declare(strict_types=1);

namespace BrianHenryIE\MoneroRpc\Daemon;

/**
 * The spent status of a key image, as reported per-image by `is_key_image_spent`.
 *
 * @see https://github.com/monero-project/monero/blob/master/src/rpc/core_rpc_server_commands_defs.h
 *      COMMAND_RPC_IS_KEY_IMAGE_SPENT: UNSPENT = 0, SPENT_IN_BLOCKCHAIN = 1, SPENT_IN_POOL = 2.
 */
enum KeyImageSpentStatus: int
{
    case Unspent = 0;
    case SpentInBlockchain = 1;
    case SpentInPool = 2;
}
