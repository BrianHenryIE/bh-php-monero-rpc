<?php

declare(strict_types=1);

namespace BrianHenryIE\MoneroRpc;

use Brick\Math\BigInteger;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \BrianHenryIE\MoneroRpc\MoneroAmount
 */
class MoneroAmountUnitTest extends TestCase
{
    /**
     * The exact conversion the former `Wallet::_transform()` truncated: '1.23' XMR is
     * 1_230_000_000_000 atomic units, not 1 XMR. See INTEGRATION_TEST_PLAN.md bug #1.
     *
     * @covers ::fromXmr
     * @covers ::toAtomicUnitsString
     */
    public function testFromXmrConvertsExactly(): void
    {
        $amount = MoneroAmount::fromXmr('1.23');

        $this->assertSame('1230000000000', $amount->toAtomicUnitsString());
    }

    /**
     * One atomic unit is the smallest representable value: 0.000000000001 XMR (12 dp).
     *
     * @covers ::fromXmr
     */
    public function testFromXmrSmallestUnit(): void
    {
        $this->assertSame('1', MoneroAmount::fromXmr('0.000000000001')->toAtomicUnitsString());
    }

    /**
     * 13 decimal places is finer than one atomic unit; it must throw, never round.
     *
     * @covers ::fromXmr
     */
    public function testFromXmrThrowsOnMoreThanTwelveDecimals(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        MoneroAmount::fromXmr('0.0000000000001');
    }

    /**
     * Trailing zeros do not change the value.
     *
     * @covers ::fromXmr
     * @covers ::isEqualTo
     */
    public function testFromXmrTrailingZerosEqual(): void
    {
        $this->assertTrue(
            MoneroAmount::fromXmr('1.230000000000')->isEqualTo(MoneroAmount::fromXmr('1.23'))
        );
    }

    /**
     * uint64 max (18446744073709551615) exceeds PHP's signed-int64 max; it must survive
     * a string round-trip losslessly — the whole reason for this class.
     *
     * @covers ::fromAtomicUnits
     * @covers ::toAtomicUnitsString
     */
    public function testAtomicUnitsAbovePhpIntMaxRoundTrip(): void
    {
        $uint64Max = '18446744073709551615';

        $this->assertGreaterThan(PHP_INT_MAX, (float) $uint64Max);
        $this->assertSame($uint64Max, MoneroAmount::fromAtomicUnits($uint64Max)->toAtomicUnitsString());
    }

    /**
     * @covers ::fromAtomicUnits
     */
    public function testFromAtomicUnitsAcceptsIntStringAndBigInteger(): void
    {
        $fromInt = MoneroAmount::fromAtomicUnits(1230000000000);
        $fromString = MoneroAmount::fromAtomicUnits('1230000000000');
        $fromBigInteger = MoneroAmount::fromAtomicUnits(BigInteger::of('1230000000000'));

        $this->assertTrue($fromInt->isEqualTo($fromString));
        $this->assertTrue($fromString->isEqualTo($fromBigInteger));
    }

    /**
     * @covers ::toXmr
     */
    public function testToXmr(): void
    {
        $this->assertSame('1.23', (string) MoneroAmount::fromAtomicUnits('1230000000000')->toXmr());
    }

    /**
     * @covers ::plus
     * @covers ::minus
     * @covers ::isZero
     */
    public function testArithmetic(): void
    {
        $a = MoneroAmount::fromAtomicUnits('1230000000000');
        $b = MoneroAmount::fromAtomicUnits('770000000000');

        $this->assertSame('2000000000000', $a->plus($b)->toAtomicUnitsString());
        $this->assertSame('460000000000', $a->minus($b)->toAtomicUnitsString());
        $this->assertTrue($a->minus($a)->isZero());
    }

    /**
     * @covers ::compareTo
     * @covers ::isEqualTo
     */
    public function testComparison(): void
    {
        $small = MoneroAmount::fromAtomicUnits('1');
        $large = MoneroAmount::fromAtomicUnits('18446744073709551615');

        $this->assertSame(-1, $small->compareTo($large));
        $this->assertSame(1, $large->compareTo($small));
        $this->assertSame(0, $small->compareTo(MoneroAmount::fromAtomicUnits('1')));
        $this->assertTrue($small->isEqualTo(MoneroAmount::fromAtomicUnits('1')));
        $this->assertFalse($small->isEqualTo($large));
    }

    /**
     * @covers ::__toString
     */
    public function testToStringIsAtomicUnits(): void
    {
        $this->assertSame('1230000000000', (string) MoneroAmount::fromAtomicUnits('1230000000000'));
    }
}
