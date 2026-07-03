<?php

declare(strict_types=1);

namespace BrianHenryIE\MoneroRpc\JsonMapperFactory;

use BrianHenryIE\MoneroRpc\MoneroAmount;

/**
 * JsonMapper factory that hydrates a bare monerod amount into a {@see MoneroAmount}.
 *
 * Registered against {@see MoneroAmount}::class in the shared FactoryRegistry (see
 * `RpcClient::buildResponseMapper()`). monerod sends amounts as bare integers of atomic
 * units — after the bigint-safe decode in `RpcClient::run()`, values above the signed
 * int64 range arrive as numeric strings, while smaller ones remain PHP ints. Both are
 * accepted here; anything else is a programming/wire error and throws.
 *
 * This mirrors bh-wp-bitcoin-gateway's `JsonMapper_Money`, adapted for shape: monerod
 * sends a scalar, not a `{amount, currency}` object.
 *
 * @see https://github.com/BrianHenryIE/bh-wp-bitcoin-gateway/blob/master/includes/api/helpers/jsonmapper/class-jsonmapper-money.php
 */
final class MoneroAmountFactory
{
    /**
     * @param mixed $value A bare atomic-unit amount: `int` or numeric `string`.
     *
     * @throws \InvalidArgumentException If $value is not an int or numeric string.
     */
    public function __invoke($value): MoneroAmount
    {
        if (is_int($value)) {
            return MoneroAmount::fromAtomicUnits($value);
        }

        if (is_string($value) && preg_match('/^-?\d+$/', $value) === 1) {
            return MoneroAmount::fromAtomicUnits($value);
        }

        throw new \InvalidArgumentException(sprintf(
            'Cannot create a MoneroAmount from %s; expected an integer or numeric string of atomic units.',
            is_scalar($value) ? var_export($value, true) : get_debug_type($value)
        ));
    }
}
