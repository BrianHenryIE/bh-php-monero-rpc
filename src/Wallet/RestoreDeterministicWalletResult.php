<?php

/**
 * Response of `restore_deterministic_wallet`.
 *
 * @see \BrianHenryIE\MoneroRpc\Wallet::restoreDeterministicWallet()
 *
 * @package brianhenryie/bh-php-monero-rpc
 */

namespace BrianHenryIE\MoneroRpc\Wallet;

final readonly class RestoreDeterministicWalletResult
{
    /**
     * @param string $address       The public address of the restored wallet.
     * @param string $info          Human readable message, e.g. "Wallet has been restored successfully."
     * @param string $seed          The 25-word mnemonic seed the wallet was restored from.
     * @param bool   $wasDeprecated Whether the mnemonic uses the deprecated (pre-2014, 13-word) format.
     */
    public function __construct(
        public string $address,
        public string $info,
        public string $seed,
        public bool $wasDeprecated,
    ) {
    }
}
