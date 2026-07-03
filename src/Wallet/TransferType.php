<?php

declare(strict_types=1);

namespace BrianHenryIE\MoneroRpc\Wallet;

/**
 * A category of transfer, used to filter `get_transfers`.
 *
 * As a REQUEST filter, `get_transfers` recognises `in`, `out`, `pending`, `failed` and
 * `pool`. `Block` (a coinbase/miner transfer) is not a request filter — it only appears
 * as the `type` of a transfer in a `get_transfers` RESPONSE — but is included here so the
 * same enum can type response models when they are added.
 *
 * NB: `incoming_transfers` uses a DIFFERENT value set — see {@see IncomingTransferType}.
 *
 * @see https://github.com/monero-project/monero/blob/master/src/wallet/wallet_rpc_server_commands_defs.h
 *      COMMAND_RPC_GET_TRANSFERS request: in / out / pending / failed / pool.
 * @see \BrianHenryIE\MoneroRpc\Wallet::getTransfers()
 */
enum TransferType: string
{
    case In = 'in';
    case Out = 'out';
    case Pending = 'pending';
    case Failed = 'failed';
    case Pool = 'pool';
    case Block = 'block';
}
