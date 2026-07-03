<?php

declare(strict_types=1);

namespace BrianHenryIE\MoneroRpc\Wallet;

/**
 * The `transfer_type` filter accepted by `incoming_transfers`.
 *
 * This is a distinct value set from {@see TransferType} (which filters `get_transfers`):
 * `incoming_transfers` classifies outputs by spend status, not direction.
 *
 * @see https://github.com/monero-project/monero/blob/master/src/wallet/wallet_rpc_server.cpp
 *      `incoming_transfers` accepts "all", "available", "unavailable".
 * @see \BrianHenryIE\MoneroRpc\Wallet::incomingTransfers()
 */
enum IncomingTransferType: string
{
    case All = 'all';
    case Available = 'available';
    case Unavailable = 'unavailable';
}
