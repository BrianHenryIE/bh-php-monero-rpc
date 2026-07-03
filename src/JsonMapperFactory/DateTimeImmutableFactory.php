<?php

declare(strict_types=1);

namespace BrianHenryIE\MoneroRpc\JsonMapperFactory;

use DateTimeImmutable;

/**
 * JsonMapper factory that hydrates a monerod epoch-seconds integer into a
 * {@see DateTimeImmutable} (UTC).
 *
 * Registered against {@see DateTimeImmutable}::class in the shared FactoryRegistry (see
 * `RpcClient::buildResponseMapper()`). This intentionally REPLACES json-mapper's native
 * `DateTimeImmutable` factory, which expects a date *string* — monerod instead sends a
 * bare integer number of seconds since the Unix epoch.
 *
 * `new DateTimeImmutable('@<seconds>')` is inherently UTC; no timezone is passed and the
 * server's local zone is never consulted.
 *
 * Applied ONLY to true epoch timestamps (`BlockHeader::$timestamp`, `Info::$adjustedTime`,
 * `Info::$startTime`, `TransactionPoolStatsStats::$oldest`) — never to durations or to
 * `unlock_time`, which remain `int`.
 *
 * monerod uses `0` as the "unset/none" epoch sentinel (e.g. `oldest` on an empty pool, or
 * the genesis block's timestamp), so `0` maps to `null` and EVERY converted epoch field is
 * typed `?DateTimeImmutable`. This `0 → null` rule is implemented HERE rather than at the
 * property type because json-mapper resolves factories by TYPE, not by field — the factory
 * cannot see a specific property's nullability — and the mapper invokes the factory for the
 * concrete value `0` (which is not PHP null) before the constructor is called. Applying the
 * rule uniformly keeps every epoch field consistent.
 *
 * This mirrors bh-wp-bitcoin-gateway's `JsonMapper_DateTimeInterface`, adapted for shape.
 *
 * @see https://github.com/BrianHenryIE/bh-wp-bitcoin-gateway/blob/master/includes/api/helpers/jsonmapper/class-jsonmapper-datetimeinterface.php
 */
final class DateTimeImmutableFactory
{
    /**
     * @param mixed $value Epoch seconds as `int` (or a numeric `string`); `0`/null → null.
     *
     * @throws \InvalidArgumentException If $value is neither null, an int, nor a numeric string.
     */
    public function __invoke($value): ?DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value) && preg_match('/^-?\d+$/', $value) === 1) {
            $value = (int) $value;
        }

        if (!is_int($value)) {
            throw new \InvalidArgumentException(sprintf(
                'Cannot create a DateTimeImmutable from %s; expected epoch seconds as an integer.',
                is_scalar($value) ? var_export($value, true) : get_debug_type($value)
            ));
        }

        // 0 means "unset" for these fields (e.g. an empty pool has no oldest transaction).
        if ($value === 0) {
            return null;
        }

        return new DateTimeImmutable('@' . $value);
    }
}
