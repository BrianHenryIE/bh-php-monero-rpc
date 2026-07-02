<?php

/**
 * Response of `restore_deterministic_wallet`.
 *
 * @see \BrianHenryIE\MoneroRpc\Wallet::restoreDeterministicWallet()
 *
 * @package brianhenryie/bh-php-monero-rpc
 */

namespace BrianHenryIE\MoneroRpc\Wallet;

interface RestoreDeterministicWalletResult
{
    /**
     * The public address of the restored wallet.
     */
    public function getAddress(): string;

    /**
     * Human readable message, e.g. "Wallet has been restored successfully."
     */
    public function getInfo(): string;

    /**
     * The 25-word mnemonic seed the wallet was restored from.
     */
    public function getSeed(): string;

    /**
     * Whether the mnemonic uses the deprecated (pre-2014, 13-word) format.
     */
    public function isWasDeprecated(): bool;
}
