<?php

declare(strict_types=1);

namespace BrianHenryIE\MoneroRpc\Wallet;

/**
 * SSL mode for the wallet's connection to a daemon, passed to `set_daemon`'s `ssl_support`.
 *
 * @see https://github.com/monero-project/monero/blob/master/contrib/epee/src/net_ssl.cpp
 *      `ssl_support_from_string()` accepts "enabled", "disabled", "autodetect".
 * @see \BrianHenryIE\MoneroRpc\Wallet::setDaemon()
 */
enum SslSupport: string
{
    case Enabled = 'enabled';
    case Disabled = 'disabled';
    case Autodetect = 'autodetect';
}
