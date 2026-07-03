<?php

declare(strict_types=1);

namespace BrianHenryIE\MoneroRpc\Wallet;

/**
 * Transaction fee priority, as accepted by `transfer`, `transfer_split` and `sweep_all`.
 *
 * The integer values match monerod's `fee_priority` enum. `Default` (0) lets the wallet
 * choose; higher values pay a higher fee for faster confirmation.
 *
 * @see https://github.com/monero-project/monero/blob/master/src/wallet/fee_priority.h
 *      `enum class fee_priority : uint32_t { Default = 0, Unimportant, Normal, Elevated, Priority };`
 */
enum TransferPriority: int
{
    case Default = 0;
    case Unimportant = 1;
    case Normal = 2;
    case Elevated = 3;
    case Priority = 4;
}
