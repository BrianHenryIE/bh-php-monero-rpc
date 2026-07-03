<?php

declare(strict_types=1);

namespace BrianHenryIE\MoneroRpc\JsonMapperFactory;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \BrianHenryIE\MoneroRpc\JsonMapperFactory\DateTimeImmutableFactory
 */
class DateTimeImmutableFactoryUnitTest extends TestCase
{
    /**
     * Epoch seconds → a UTC DateTimeImmutable at that instant.
     *
     * @covers ::__invoke
     */
    public function testFromEpochSecondsIsUtc(): void
    {
        $result = ( new DateTimeImmutableFactory() )(1609459200); // 2021-01-01T00:00:00Z

        $this->assertNotNull($result);
        $this->assertSame(1609459200, $result->getTimestamp());
        // The '@<seconds>' constructor form is inherently UTC (zero offset).
        $this->assertSame(0, $result->getOffset());
        $this->assertSame('2021-01-01 00:00:00', $result->format('Y-m-d H:i:s'));
    }

    /**
     * `0` means "unset/none" (e.g. the oldest transaction of an empty pool) → null.
     *
     * @covers ::__invoke
     */
    public function testZeroMapsToNull(): void
    {
        $this->assertNull(( new DateTimeImmutableFactory() )(0));
    }

    /**
     * @covers ::__invoke
     */
    public function testNullMapsToNull(): void
    {
        $this->assertNull(( new DateTimeImmutableFactory() )(null));
    }

    /**
     * @covers ::__invoke
     */
    public function testNumericStringAccepted(): void
    {
        $result = ( new DateTimeImmutableFactory() )('1609459200');

        $this->assertNotNull($result);
        $this->assertSame(1609459200, $result->getTimestamp());
    }

    /**
     * @covers ::__invoke
     */
    public function testNonNumericValueThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ( new DateTimeImmutableFactory() )('2021-01-01');
    }
}
