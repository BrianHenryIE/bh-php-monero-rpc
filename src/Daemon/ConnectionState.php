<?php

declare(strict_types=1);

namespace BrianHenryIE\MoneroRpc\Daemon;

/**
 * A peer connection's protocol state, as reported by `get_connections`'s `state` field.
 *
 * The wire values are the strings produced by monerod's `get_protocol_state_string()` —
 * note they are NOT prefixed with `state_` like the underlying C++ enumerators.
 * `Unknown` is monerod's `default:` fallback and is included so a valid response can
 * never fail to hydrate.
 *
 * @see https://github.com/monero-project/monero/blob/master/src/cryptonote_basic/connection_context.h
 *      `get_protocol_state_string()` and `enum state`.
 */
enum ConnectionState: string
{
    case BeforeHandshake = 'before_handshake';
    case Synchronizing = 'synchronizing';
    case Standby = 'standby';
    case Normal = 'normal';
    case Unknown = 'unknown';
}
