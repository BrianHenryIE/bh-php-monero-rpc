<?php

/**
 * @see https://github.com/monero-project/monero/blob/e06129bb4d1076f4f2cebabddcee09f1e9e30dcc/src/rpc/core_rpc_server_commands_defs.h#L101-L112
 */

namespace BrianHenryIE\MoneroRpc\Daemon;

/**
 * Base for daemon RPC responses that carry monerod's standard `status`/`untrusted` fields.
 *
 * Concrete responses extend this and forward both values via `parent::__construct(...)`; the
 * JsonMapper Constructor middleware hydrates them from the response body like any other field.
 *
 * It is deliberately non-abstract: endpoints that return only `status`/`untrusted` (e.g.
 * `stop_mining`, `save_bc`, `stop_daemon`) map directly to this class.
 */
readonly class ResponseBase
{
    /**
     * @param string $status    General RPC error code. "OK" means everything looks good.
     *                          "OK"|"BUSY"|"Mining never started"|"NOT MINING"
     * @param bool   $untrusted States if the result is obtained using the bootstrap mode, and is
     *                          therefore not trusted (true), or when the daemon is fully synced and
     *                          thus handles the RPC locally (false).
     */
    public function __construct(
        public string $status,
        public bool $untrusted,
    ) {
    }
}
