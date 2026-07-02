<?php

/**
 * Permanent guarantee of PHP84_READONLY_MODELS_PLAN.md design decision 5 (replaces the throwaway
 * ReadonlyHydrationSpikeTest): response models require their fields, a missing required field throws
 * a context-rich {@see IncompleteRpcResponseException} (without leaking the raw body into the
 * message), documented-optional fields hydrate to their default, and unknown extra fields are
 * ignored.
 *
 * @package brianhenryie/bh-php-monero-rpc
 */

namespace BrianHenryIE\MoneroRpc\Model;

use BrianHenryIE\MoneroRpc\Daemon\BlockCount;
use BrianHenryIE\MoneroRpc\Daemon\Height;
use BrianHenryIE\MoneroRpc\Exception\IncompleteRpcResponseException;
use BrianHenryIE\MoneroRpc\RpcClient;
use PHPUnit\Framework\TestCase;

class ResponseModelStrictnessTest extends TestCase
{
    public function testMissingRequiredFieldThrowsWithContext(): void
    {
        // BlockCount requires `count`; this response omits it.
        $body = '{"status":"OK","untrusted":false}';

        try {
            RpcClient::assertResponseComplete('get_block_count', BlockCount::class, $body);
            self::fail('Expected IncompleteRpcResponseException.');
        } catch (IncompleteRpcResponseException $exception) {
            $message = $exception->getMessage();
            self::assertStringContainsString('get_block_count', $message);
            self::assertStringContainsString('BlockCount', $message);
            self::assertStringContainsString('count', $message);
            // The present keys are reported to aid debugging.
            self::assertStringContainsString('status', $message);
            // The raw body is retrievable but NOT in the message.
            self::assertSame($body, $exception->getResponseBody());
        }
    }

    public function testRawBodyIsRetrievableButNotInMessage(): void
    {
        // A required field is missing; the body also carries a "secret" that must not leak to logs.
        $body = '{"status":"OK","untrusted":false,"seed":"correct horse battery staple"}';

        try {
            RpcClient::assertResponseComplete('query_key', BlockCount::class, $body);
            self::fail('Expected IncompleteRpcResponseException.');
        } catch (IncompleteRpcResponseException $exception) {
            self::assertStringNotContainsString('correct horse battery staple', $exception->getMessage());
            self::assertStringContainsString('correct horse battery staple', $exception->getResponseBody());
        }
    }

    public function testMissingOptionalFieldHydratesToDefault(): void
    {
        // Height::$hash is documented-optional (wallet get_height omits it).
        $body = '{"height":42}';

        RpcClient::assertResponseComplete('get_height', Height::class, $body);
        $height = RpcClient::buildResponseMapper()->mapToClassFromString($body, Height::class);

        self::assertSame(42, $height->height);
        self::assertNull($height->hash);
    }

    public function testExtraUnknownFieldIsIgnored(): void
    {
        $body = '{"height":42,"hash":"abc","some_new_monerod_field":123}';

        RpcClient::assertResponseComplete('get_height', Height::class, $body);
        $height = RpcClient::buildResponseMapper()->mapToClassFromString($body, Height::class);

        self::assertSame(42, $height->height);
        self::assertSame('abc', $height->hash);
    }

    public function testCompleteResponseDoesNotThrow(): void
    {
        $this->expectNotToPerformAssertions();

        RpcClient::assertResponseComplete(
            'get_block_count',
            BlockCount::class,
            '{"count":123,"status":"OK","untrusted":false}'
        );
    }
}
