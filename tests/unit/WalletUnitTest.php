<?php

namespace BrianHenryIE\MoneroRpc;

use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriFactoryInterface;
use PsrMock\Psr17\RequestFactory;
use PsrMock\Psr17\StreamFactory;
use PsrMock\Psr18\Client;
use PsrMock\Psr7\Response;
use PsrMock\Psr7\Uri;

/**
 * @coversDefaultClass \BrianHenryIE\MoneroRpc\Wallet
 */
class WalletUnitTest extends TestCase
{
    private function getWalletClient(string $path, string $responseBody): Wallet
    {
        $client = new Client();

        $uri = new Uri("https://127.0.0.1:18083/$path");

        $uriFactory = Mockery::mock(UriFactoryInterface::class);
        $uriFactory->shouldReceive('createUri')->andReturn($uri);

        $walletRpcClient = new Wallet(
            $uriFactory,
            new RequestFactory(),
            $client,
            new StreamFactory()
        );

        $streamFactory = new StreamFactory();
        $responseStream = $streamFactory->createStream($responseBody);

        $response = (new Response())->withBody($responseStream);

        $client->addResponse(
            'POST',
            "https://127.0.0.1:18083/$path",
            $response
        );

        return $walletRpcClient;
    }

    /**
     * @covers ::restoreDeterministicWallet
     */
    public function testRestoreDeterministicWallet(): void
    {
        $responseBody = <<<'EOD'
{
  "id": "0",
  "jsonrpc": "2.0",
  "result": {
    "address": "43zjHrEvKytNk8YG7JEa2M9XJsPtfkG23jFBukXBj43sETTrwZhVYr1Pup4HH9qox9GuNeUuTvquyYc8Sk8PTVSaPbhVN81",
    "info": "Wallet has been restored successfully.",
    "seed": "buzzer robot maverick doing each coexist remedy dilute tattoo somewhere lullaby kennel unopened donuts occur inroads biweekly under knowledge uphill idled vessel macro midst doing",
    "was_deprecated": false
  }
}
EOD;

        $walletRpcClient = $this->getWalletClient('json_rpc', $responseBody);

        $result = $walletRpcClient->restoreDeterministicWallet(
            'miner_wallet',
            'integration-test-password',
            'buzzer robot maverick doing each coexist remedy dilute tattoo somewhere lullaby kennel'
            . ' unopened donuts occur inroads biweekly under knowledge uphill idled vessel macro midst doing'
        );

        self::assertSame(
            '43zjHrEvKytNk8YG7JEa2M9XJsPtfkG23jFBukXBj43sETTrwZhVYr1Pup4HH9qox9GuNeUuTvquyYc8Sk8PTVSaPbhVN81',
            $result->address
        );
        self::assertSame('Wallet has been restored successfully.', $result->info);
        self::assertFalse($result->wasDeprecated);
    }

    /**
     * `_transform()` previously declared `int $amount`, silently truncating
     * fractional XMR amounts passed as strings ('1.23' became 1 XMR).
     *
     * @covers ::_transform
     */
    public function testTransformFractionalXmrAmountToAtomicUnits(): void
    {
        $walletRpcClient = $this->getWalletClient('json_rpc', '{}');

        self::assertSame(1230000000000, $walletRpcClient->_transform('1.23'));
        self::assertSame(1000000000000, $walletRpcClient->_transform(1));
        self::assertSame(0, $walletRpcClient->_transform());
    }
}
