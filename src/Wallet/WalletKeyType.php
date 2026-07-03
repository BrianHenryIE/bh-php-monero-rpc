<?php

declare(strict_types=1);

namespace BrianHenryIE\MoneroRpc\Wallet;

/**
 * The kind of key requested from `query_key`.
 *
 * @see \BrianHenryIE\MoneroRpc\Wallet::queryKey()
 */
enum WalletKeyType: string
{
    case ViewKey = 'view_key';
    case SpendKey = 'spend_key';
    case Mnemonic = 'mnemonic';
}
