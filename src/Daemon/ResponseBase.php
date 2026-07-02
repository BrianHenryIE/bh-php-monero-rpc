<?php

/**
 * @see https://github.com/monero-project/monero/blob/e06129bb4d1076f4f2cebabddcee09f1e9e30dcc/src/rpc/core_rpc_server_commands_defs.h#L101-L112
 */

namespace BrianHenryIE\MoneroRpc\Daemon;

interface ResponseBase
{
    /**
     * General RPC error code. "OK" means everything looks good.
     *
     * "OK"|"BUSY"|"Mining never started"|"NOT MINING"
     */
    public function getStatus(): string;

    /**
     * States if the result is obtained using the bootstrap mode, and is therefore not trusted (true), or when the
     * daemon is fully synced and thus handles the RPC locally (false)
     */
    public function getUntrusted(): bool;
}
