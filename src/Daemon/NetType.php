<?php

declare(strict_types=1);

namespace BrianHenryIE\MoneroRpc\Daemon;

/**
 * The Monero network a daemon is running on, as reported by `get_info`'s `nettype`.
 *
 * `fakechain` is what a regtest/`--regtest` daemon reports (the integration suite asserts
 * this).
 *
 * @see https://github.com/monero-project/monero/blob/master/src/rpc/core_rpc_server.cpp
 *      `res.nettype = net_type == MAINNET ? "mainnet" : ... : "fakechain";`
 */
enum NetType: string
{
    case Mainnet = 'mainnet';
    case Testnet = 'testnet';
    case Stagenet = 'stagenet';
    case Fakechain = 'fakechain';
}
