<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

use BrianHenryIE\MoneroRpc\MoneroAmount;

/**
 * Response of the wallet `sweep_all` RPC, which may produce several transactions. Every
 * list is parallel (index N describes the Nth resulting transaction).
 */
final readonly class SweepAllResult
{
    /**
     * @param string[]        $txHashList        Transaction ids.
     * @param MoneroAmount[]  $amountList        Amount swept by each transaction, in atomic units.
     * @param MoneroAmount[]  $feeList           Fee paid by each transaction, in atomic units.
     * @param int[]           $weightList        Weight of each transaction.
     * @param string[]        $txKeyList         Per-transaction secret keys.
     * @param AmountsByDest[]  $amountsByDestList Per-transaction, per-destination amounts.
     * @param SpentKeyImages[] $spentKeyImagesList Per-transaction spent key images.
     * @param string[]        $txBlobList        Per-transaction raw hex (only with get_tx_hex).
     * @param string[]        $txMetadataList    Per-transaction metadata (only with get_tx_metadata).
     * @param string          $multisigTxset     Multisig tx set (empty unless multisig).
     * @param string          $unsignedTxset     Unsigned tx set (empty unless building unsigned).
     */
    public function __construct(
        public array $txHashList,
        public array $amountList,
        public array $feeList,
        public array $weightList,
        public array $txKeyList = [],
        public array $amountsByDestList = [],
        public array $spentKeyImagesList = [],
        public array $txBlobList = [],
        public array $txMetadataList = [],
        public string $multisigTxset = '',
        public string $unsignedTxset = '',
    ) {
    }
}
