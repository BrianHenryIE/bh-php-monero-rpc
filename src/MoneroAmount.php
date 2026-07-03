<?php

declare(strict_types=1);

namespace BrianHenryIE\MoneroRpc;

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\Exception\MathException;

/**
 * An immutable Monero amount, stored exactly as an integer number of atomic units.
 *
 * Monero's smallest unit is the *atomic unit* (colloquially "piconero"): 1 XMR =
 * 10^12 atomic units. monerod reports and accepts amounts as **unsigned 64-bit**
 * integers of atomic units.
 *
 * PHP's `int` is *signed* 64-bit (max ~9.22e18). Monero's cumulative emission is
 * already ~1.84e19 atomic units, so fields such as `already_generated_coins` cannot
 * be represented by PHP `int` at all — and `json_decode()` silently degrades an
 * out-of-range integer to a lossy `float` unless decoded with `JSON_BIGINT_AS_STRING`.
 * Using `int` for currency is therefore a live precision bug, not merely a style
 * choice. This class wraps {@see BigInteger} (backed by ext-bcmath, or GMP when
 * available) to represent the full uint64 range — and beyond — exactly.
 *
 * A dedicated value object (rather than a bare {@see BigInteger} or brick/money) is
 * used because there is a single currency here, and a domain type reads better in
 * signatures and prevents accidental mixing of atomic-unit and XMR-denominated
 * numbers.
 */
final readonly class MoneroAmount implements \Stringable
{
    /**
     * The number of atomic units in one whole XMR: 10^12.
     */
    private const ATOMIC_UNITS_PER_XMR = '1000000000000';

    /**
     * The number of decimal places of an XMR value; equivalently, the power of ten
     * relating XMR to atomic units.
     */
    private const XMR_DECIMALS = 12;

    /**
     * @param BigInteger $atomicUnits The amount as an exact integer number of atomic units.
     */
    private function __construct(
        public BigInteger $atomicUnits,
    ) {
    }

    /**
     * Create an amount from a raw atomic-unit value.
     *
     * Accepts a PHP `int`, a numeric string (e.g. the output of a
     * `JSON_BIGINT_AS_STRING` decode, which is how large monerod values arrive), or
     * an existing {@see BigInteger}.
     *
     * @param int|string|BigInteger $atomicUnits
     *
     * @throws MathException If a string value is not a valid integer.
     */
    public static function fromAtomicUnits(int|string|BigInteger $atomicUnits): self
    {
        return new self(BigInteger::of($atomicUnits));
    }

    /**
     * Create an amount from an XMR-denominated decimal string, e.g. `'1.23'`.
     *
     * The conversion is exact. An XMR value has at most 12 decimal places (one atomic
     * unit); a value with more precision than that cannot be represented and is a
     * caller error, so this method **throws** rather than silently rounding. This
     * replaces the former `Wallet::_transform()`, which used `intval(bcmul(...))` and
     * both truncated fractional input and overflowed above PHP_INT_MAX.
     *
     * @param string $xmrDecimal A decimal XMR amount, e.g. `'1.23'` or `'0.000000000001'`.
     *
     * @throws \InvalidArgumentException If the value has more than 12 decimal places.
     * @throws MathException             If the value is not a valid decimal number.
     */
    public static function fromXmr(string $xmrDecimal): self
    {
        $xmr = BigDecimal::of($xmrDecimal);

        if ($xmr->getScale() > self::XMR_DECIMALS) {
            throw new \InvalidArgumentException(sprintf(
                'XMR amount "%s" has more than %d decimal places; it cannot be represented '
                . 'exactly in atomic units.',
                $xmrDecimal,
                self::XMR_DECIMALS
            ));
        }

        // Exact: scale is <= 12, so scaling up by 10^12 yields an integer with no rounding.
        $atomicUnits = $xmr->multipliedBy(self::ATOMIC_UNITS_PER_XMR)->toBigInteger();

        return new self($atomicUnits);
    }

    /**
     * The amount as a base-10 atomic-unit string, e.g. `'1230000000000'`. Lossless for
     * any magnitude, including values above PHP_INT_MAX.
     */
    public function toAtomicUnitsString(): string
    {
        return $this->atomicUnits->toString();
    }

    /**
     * The amount as an exact XMR-denominated {@see BigDecimal}, e.g. `1.23`.
     */
    public function toXmr(): BigDecimal
    {
        return $this->atomicUnits->toBigDecimal()->dividedByExact(self::ATOMIC_UNITS_PER_XMR);
    }

    public function isEqualTo(self $that): bool
    {
        return $this->atomicUnits->isEqualTo($that->atomicUnits);
    }

    /**
     * @return int -1, 0 or 1 as this amount is less than, equal to, or greater than $that.
     */
    public function compareTo(self $that): int
    {
        return $this->atomicUnits->compareTo($that->atomicUnits);
    }

    public function plus(self $that): self
    {
        return new self($this->atomicUnits->plus($that->atomicUnits));
    }

    public function minus(self $that): self
    {
        return new self($this->atomicUnits->minus($that->atomicUnits));
    }

    public function isZero(): bool
    {
        return $this->atomicUnits->isZero();
    }

    /**
     * The atomic-unit string representation. See {@see toAtomicUnitsString()}.
     */
    public function __toString(): string
    {
        return $this->toAtomicUnitsString();
    }
}
