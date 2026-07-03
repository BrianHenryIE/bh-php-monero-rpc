<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

use BrianHenryIE\MoneroRpc\MoneroAmount;

/**
 * Response of the wallet `sweep_single` RPC (sweeps one specific output; a single transaction).
 */
final readonly class SweepSingleResult
{
    /**
     * @param string          $txHash         The transaction id.
     * @param string          $txKey          The transaction secret key.
     * @param MoneroAmount    $amount         Amount swept, in atomic units.
     * @param MoneroAmount    $fee            Fee paid, in atomic units.
     * @param int             $weight         Transaction weight.
     * @param ?SpentKeyImages  $spentKeyImages Key images the transaction spends.
     * @param string          $txBlob         Raw tx hex (only with get_tx_hex).
     * @param string          $txMetadata     Tx metadata blob (only with get_tx_metadata).
     * @param string          $multisigTxset  Multisig tx set (empty unless multisig).
     * @param string          $unsignedTxset  Unsigned tx set (empty unless building unsigned).
     */
    public function __construct(
        public string $txHash,
        public string $txKey,
        public MoneroAmount $amount,
        public MoneroAmount $fee,
        public int $weight,
        public ?SpentKeyImages $spentKeyImages = null,
        public string $txBlob = '',
        public string $txMetadata = '',
        public string $multisigTxset = '',
        public string $unsignedTxset = '',
    ) {
    }
}
