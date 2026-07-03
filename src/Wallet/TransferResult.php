<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

use BrianHenryIE\MoneroRpc\MoneroAmount;

/**
 * Response of the wallet `transfer` RPC (single aggregated transaction).
 *
 * `txBlob`/`txMetadata` are populated only when the request set `get_tx_hex`/`get_tx_metadata`;
 * `multisigTxset`/`unsignedTxset` are empty for a normal (non-multisig, signed) transfer.
 */
final readonly class TransferResult
{
    /**
     * @param MoneroAmount    $amount        Total amount sent, in atomic units.
     * @param MoneroAmount    $fee           Fee paid, in atomic units.
     * @param int             $weight        Transaction weight.
     * @param string          $txHash        The transaction id.
     * @param string          $txKey         The transaction secret key.
     * @param ?AmountsByDest   $amountsByDest  Per-destination amounts.
     * @param ?SpentKeyImages  $spentKeyImages Key images the transaction spends.
     * @param string          $txBlob        Raw tx hex (only with get_tx_hex).
     * @param string          $txMetadata    Tx metadata blob (only with get_tx_metadata).
     * @param string          $multisigTxset Multisig tx set (empty unless multisig).
     * @param string          $unsignedTxset Unsigned tx set (empty unless building unsigned).
     */
    public function __construct(
        public MoneroAmount $amount,
        public MoneroAmount $fee,
        public int $weight,
        public string $txHash,
        public string $txKey,
        public ?AmountsByDest $amountsByDest = null,
        public ?SpentKeyImages $spentKeyImages = null,
        public string $txBlob = '',
        public string $txMetadata = '',
        public string $multisigTxset = '',
        public string $unsignedTxset = '',
    ) {
    }
}
