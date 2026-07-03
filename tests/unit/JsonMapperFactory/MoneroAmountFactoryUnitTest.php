<?php

declare(strict_types=1);

namespace BrianHenryIE\MoneroRpc\JsonMapperFactory;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \BrianHenryIE\MoneroRpc\JsonMapperFactory\MoneroAmountFactory
 */
class MoneroAmountFactoryUnitTest extends TestCase
{
    /**
     * @covers ::__invoke
     */
    public function testFromInt(): void
    {
        $amount = ( new MoneroAmountFactory() )(1230000000000);

        $this->assertSame('1230000000000', $amount->toAtomicUnitsString());
    }

    /**
     * A value above PHP_INT_MAX arrives as a numeric string from the bigint-safe decode.
     *
     * @covers ::__invoke
     */
    public function testFromNumericStringAbovePhpIntMax(): void
    {
        $amount = ( new MoneroAmountFactory() )('18446744073709551615');

        $this->assertSame('18446744073709551615', $amount->toAtomicUnitsString());
    }

    /**
     * @covers ::__invoke
     */
    public function testNonNumericStringThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ( new MoneroAmountFactory() )('not-a-number');
    }

    /**
     * A float would be a lossy input — reject it rather than silently accepting.
     *
     * @covers ::__invoke
     */
    public function testFloatThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ( new MoneroAmountFactory() )(1.23);
    }
}
