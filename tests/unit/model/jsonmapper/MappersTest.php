<?php

namespace BrianHenryIE\MoneroRpc\Model\JsonMapper;

use BrianHenryIE\MoneroRpc\Daemon\AltBlocksHashes;
use BrianHenryIE\MoneroRpc\Daemon\Block;
use BrianHenryIE\MoneroRpc\Daemon\BlockCount;
use BrianHenryIE\MoneroRpc\Daemon\BlockHeaderBy;
use BrianHenryIE\MoneroRpc\Daemon\Connections;
use BrianHenryIE\MoneroRpc\Daemon\HardForkInfo;
use BrianHenryIE\MoneroRpc\Daemon\Info;
use BrianHenryIE\MoneroRpc\Daemon\KeyImageSpent;
use BrianHenryIE\MoneroRpc\Daemon\InPeers;
use BrianHenryIE\MoneroRpc\Daemon\Limit;
use BrianHenryIE\MoneroRpc\Daemon\LogCategories;
use BrianHenryIE\MoneroRpc\Daemon\OutPeers;
use BrianHenryIE\MoneroRpc\Daemon\Outs;
use BrianHenryIE\MoneroRpc\Daemon\MiningStatus;
use BrianHenryIE\MoneroRpc\Daemon\PeerList;
use BrianHenryIE\MoneroRpc\Daemon\ResponseBase;
use BrianHenryIE\MoneroRpc\Daemon\SendRawTransactionResult;
use BrianHenryIE\MoneroRpc\Daemon\SubmitBlockResult;
use BrianHenryIE\MoneroRpc\Daemon\TransactionPool;
use BrianHenryIE\MoneroRpc\Daemon\TransactionPoolStats;
use BrianHenryIE\MoneroRpc\Daemon\Transactions;
use BrianHenryIE\MoneroRpc\RpcClient;
use BrianHenryIE\MoneroRpc\Wallet\AccountTags;
use BrianHenryIE\MoneroRpc\Wallet\Accounts;
use BrianHenryIE\MoneroRpc\Wallet\AddressBook;
use BrianHenryIE\MoneroRpc\Wallet\AddressBookIndex;
use BrianHenryIE\MoneroRpc\Wallet\AddressIndex;
use BrianHenryIE\MoneroRpc\Wallet\AddressValidation;
use BrianHenryIE\MoneroRpc\Wallet\CreatedAccount;
use BrianHenryIE\MoneroRpc\Wallet\CreatedAddress;
use BrianHenryIE\MoneroRpc\Wallet\GetAttribute;
use BrianHenryIE\MoneroRpc\Wallet\TxNotes;
use BrianHenryIE\MoneroRpc\Wallet\CheckReserveProof;
use BrianHenryIE\MoneroRpc\Wallet\CheckSpendProof;
use BrianHenryIE\MoneroRpc\Wallet\KeyImagesExport;
use BrianHenryIE\MoneroRpc\Wallet\Languages;
use BrianHenryIE\MoneroRpc\Wallet\MakeUriResult;
use BrianHenryIE\MoneroRpc\Wallet\OutputsData;
use BrianHenryIE\MoneroRpc\Wallet\ParseUriResult;
use BrianHenryIE\MoneroRpc\Wallet\SplitIntegratedAddress;
use BrianHenryIE\MoneroRpc\Wallet\CheckTxKey;
use BrianHenryIE\MoneroRpc\Wallet\CheckTxProof;
use BrianHenryIE\MoneroRpc\Wallet\DescribeTransferResult;
use BrianHenryIE\MoneroRpc\Wallet\IncomingTransfers;
use BrianHenryIE\MoneroRpc\Wallet\Signature;
use BrianHenryIE\MoneroRpc\Wallet\TxKey;
use BrianHenryIE\MoneroRpc\Wallet\Verify;
use BrianHenryIE\MoneroRpc\Wallet\Payments;
use BrianHenryIE\MoneroRpc\Wallet\RelayTxResult;
use BrianHenryIE\MoneroRpc\Wallet\SweepAllResult;
use BrianHenryIE\MoneroRpc\Wallet\SweepDust;
use BrianHenryIE\MoneroRpc\Wallet\SweepSingleResult;
use BrianHenryIE\MoneroRpc\Wallet\TransferByTxid;
use BrianHenryIE\MoneroRpc\Wallet\TransferResult;
use BrianHenryIE\MoneroRpc\Wallet\Transfers;
use BrianHenryIE\MoneroRpc\Wallet\TransferSplitResult;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Regression net: every committed daemon fixture must hydrate into its readonly response model
 * through the single shared hydrator ({@see RpcClient::buildResponseMapper()}). Because the models'
 * fields are required (PHP84_READONLY_MODELS_PLAN.md design decision 5), a fixture missing a field
 * the model requires fails here — this is the intended tripwire against stale fixtures and
 * mis-typed models.
 */
class MappersTest extends \PHPUnit\Framework\TestCase
{
    /**
     *
     * @return array<string, array{0: string, 1: class-string}>
     */
    public static function data(): array
    {
        return [
            'get_alt_blocks_hashes.json' => [ 'get_alt_blocks_hashes.json', AltBlocksHashes::class ],
            'json_rpc-getblockheaderbyheight.json' => ['json_rpc-getblockheaderbyheight.json', BlockHeaderBy::class],
            'stop_daemon.json' => ['stop_daemon.json', ResponseBase::class],
            'get_limit.json' => ['get_limit.json', Limit::class],
            'json_rpc-get_info.json' => ['json_rpc-get_info.json', Info::class],
            'json_rpc-hard_fork_info.json' => ['json_rpc-hard_fork_info.json', HardForkInfo::class],
            'json_rpc-getlastblockheader.json' => ['json_rpc-getlastblockheader.json', BlockHeaderBy::class],
            'stop_mining.json' => ['stop_mining.json', ResponseBase::class],
            'get_peer_list.json' => ['get_peer_list.json', PeerList::class ],
            'json_rpc-getblock.json' => ['json_rpc-getblock.json', Block::class],
            'mining_status.json' => ['mining_status.json', MiningStatus::class],
            'get_transaction_pool_stats.json' => ['get_transaction_pool_stats.json', TransactionPoolStats::class],
            'json_rpc-getblockcount.json' => ['json_rpc-getblockcount.json', BlockCount::class],
            'in_peers.json' => ['in_peers.json', InPeers::class],
            'out_peers.json' => ['out_peers.json', OutPeers::class],
            'get_outs.json' => ['get_outs.json', Outs::class],
            'is_key_image_spent.json' => ['is_key_image_spent.json', KeyImageSpent::class],
            'get_transactions.json' => ['get_transactions.json', Transactions::class],
            'get_transaction_pool.json' => ['get_transaction_pool.json', TransactionPool::class],
            'send_raw_transaction.json' => ['send_raw_transaction.json', SendRawTransactionResult::class],
            'json_rpc-submitblock.json' => ['json_rpc-submitblock.json', SubmitBlockResult::class],
            'set_log_categories.json' => ['set_log_categories.json', LogCategories::class],
            'json_rpc-getblockheaderbyhash.json' => ['json_rpc-getblockheaderbyhash.json', BlockHeaderBy::class],
            'set_limit.json' => ['set_limit.json', Limit::class],
            'get_connections.json' => ['get_connections.json', Connections::class],
            'wallet/transfer.json' => ['wallet/transfer.json', TransferResult::class],
            'wallet/transfer_split.json' => ['wallet/transfer_split.json', TransferSplitResult::class],
            'wallet/get_transfers.json' => ['wallet/get_transfers.json', Transfers::class],
            'wallet/incoming_transfers.json' => ['wallet/incoming_transfers.json', IncomingTransfers::class],
            'wallet/get_transfer_by_txid.json' => ['wallet/get_transfer_by_txid.json', TransferByTxid::class],
            'wallet/sweep_dust.json' => ['wallet/sweep_dust.json', SweepDust::class],
            'wallet/relay_tx.json' => ['wallet/relay_tx.json', RelayTxResult::class],
            'wallet/sweep_all.json' => ['wallet/sweep_all.json', SweepAllResult::class],
            'wallet/sweep_single.json' => ['wallet/sweep_single.json', SweepSingleResult::class],
            'wallet/get_payments.json' => ['wallet/get_payments.json', Payments::class],
            'wallet/describe_transfer.json' => ['wallet/describe_transfer.json', DescribeTransferResult::class],
            'wallet/get_tx_key.json' => ['wallet/get_tx_key.json', TxKey::class],
            'wallet/check_tx_key.json' => ['wallet/check_tx_key.json', CheckTxKey::class],
            'wallet/get_tx_proof.json' => ['wallet/get_tx_proof.json', Signature::class],
            'wallet/check_tx_proof.json' => ['wallet/check_tx_proof.json', CheckTxProof::class],
            'wallet/check_spend_proof.json' => ['wallet/check_spend_proof.json', CheckSpendProof::class],
            'wallet/check_reserve_proof.json' => ['wallet/check_reserve_proof.json', CheckReserveProof::class],
            'wallet/sign.json' => ['wallet/sign.json', Signature::class],
            'wallet/verify.json' => ['wallet/verify.json', Verify::class],
            'wallet/validate_address.json' => ['wallet/validate_address.json', AddressValidation::class],
            'wallet/make_uri.json' => ['wallet/make_uri.json', MakeUriResult::class],
            'wallet/parse_uri.json' => ['wallet/parse_uri.json', ParseUriResult::class],
            'wallet/export_outputs.json' => ['wallet/export_outputs.json', OutputsData::class],
            'wallet/export_key_images.json' => ['wallet/export_key_images.json', KeyImagesExport::class],
            'wallet/get_languages.json' => ['wallet/get_languages.json', Languages::class],
            'wallet/get_address_index.json' => ['wallet/get_address_index.json', AddressIndex::class],
            'wallet/split_integrated_address.json' => ['wallet/split_integrated_address.json', SplitIntegratedAddress::class],
            'wallet/get_accounts.json' => ['wallet/get_accounts.json', Accounts::class],
            'wallet/create_account.json' => ['wallet/create_account.json', CreatedAccount::class],
            'wallet/create_address.json' => ['wallet/create_address.json', CreatedAddress::class],
            'wallet/add_address_book.json' => ['wallet/add_address_book.json', AddressBookIndex::class],
            'wallet/get_address_book.json' => ['wallet/get_address_book.json', AddressBook::class],
            'wallet/get_attribute.json' => ['wallet/get_attribute.json', GetAttribute::class],
            'wallet/get_account_tags.json' => ['wallet/get_account_tags.json', AccountTags::class],
            'wallet/get_tx_notes.json' => ['wallet/get_tx_notes.json', TxNotes::class],
        ];
    }

    /**
     * @template T of object
     * @param string $filename The test .json file.
     * @param class-string<T> $type The object type to cast/deserialize the response to.
     */
    #[DataProvider('data')]
    public function testMappers(string $filename, string $type): void
    {
        // A filename with a directory separator is treated as relative to tests/_data;
        // a bare name defaults to the daemon fixtures directory.
        $relativePath = str_contains($filename, '/') ? $filename : ('daemon/' . $filename);
        $json = (string) file_get_contents(__DIR__ . '/../../../_data/' . $relativePath);

        // `json_rpc` responses wrap the payload in an envelope; the model maps the `result` (this
        // mirrors RpcClient::run()). "Other" endpoint bodies ARE the payload. Decode with
        // JSON_BIGINT_AS_STRING exactly as run() does, so large integers reach the mapper as exact
        // numeric strings rather than lossy floats.
        $decoded = json_decode($json, false, 512, JSON_BIGINT_AS_STRING);
        $payload = (is_object($decoded) && property_exists($decoded, 'result')) ? $decoded->result : $decoded;

        $mapper = RpcClient::buildResponseMapper();

        try {
            if (is_array($payload)) {
                $result = $mapper->mapToClassArray($payload, $type);
            } else {
                $result = $mapper->mapToClass($payload, $type);
            }
        } catch (\Throwable $throwable) {
            self::fail($filename . ' : ' . get_class($throwable) . ' - ' . $throwable->getMessage());
        }

        self::assertInstanceOf($type, $result);
    }

    /**
     * Factory regression: an amount above PHP_INT_MAX (here uint64 max) must hydrate into an exact
     * MoneroAmount, not a lossy float/int. Mirrors RpcClient::run()'s bigint-safe decode.
     */
    public function testBigIntegerAmountHydratesExactly(): void
    {
        $uint64Max = '18446744073709551615';
        $json = <<<JSON
        {
          "id": 0,
          "jsonrpc": "2.0",
          "result": {
            "block_header": {
              "block_size": 1, "block_weight": 1, "cumulative_difficulty": 1,
              "cumulative_difficulty_top64": 0, "depth": 0, "difficulty": 1, "difficulty_top64": 0,
              "hash": "h", "height": 1, "long_term_weight": 1, "major_version": 16, "miner_tx_hash": "m",
              "minor_version": 16, "nonce": 0, "num_txes": 0, "orphan_status": false, "pow_hash": "",
              "prev_hash": "p", "reward": {$uint64Max}, "timestamp": 1700000000,
              "wide_cumulative_difficulty": "0x1", "wide_difficulty": "0x1"
            },
            "status": "OK", "untrusted": false
          }
        }
        JSON;

        $decoded = json_decode($json, false, 512, JSON_BIGINT_AS_STRING);
        $result = RpcClient::buildResponseMapper()->mapToClass($decoded->result, BlockHeaderBy::class);

        self::assertSame($uint64Max, $result->blockHeader->reward->toAtomicUnitsString());
    }
}
