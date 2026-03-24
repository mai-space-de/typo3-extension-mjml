<?php

declare(strict_types=1);

namespace MaiSpaceDe\Mjml\Tests\Unit\Middleware;

use MaiSpaceDe\Mjml\Exception\MjmlException;
use MaiSpaceDe\Mjml\Middleware\MjmlMiddleware;
use MaiSpaceDe\Mjml\Service\MjmlService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class MjmlMiddlewareTest extends UnitTestCase
{
    private MjmlService&MockObject $mjmlServiceMock;
    private ResponseFactoryInterface&MockObject $responseFactoryMock;
    private StreamFactoryInterface&MockObject $streamFactoryMock;
    private ExtensionConfiguration&MockObject $extensionConfigurationMock;
    private RequestHandlerInterface&MockObject $handlerMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mjmlServiceMock = $this->createMock(MjmlService::class);
        $this->responseFactoryMock = $this->createMock(ResponseFactoryInterface::class);
        $this->streamFactoryMock = $this->createMock(StreamFactoryInterface::class);
        $this->extensionConfigurationMock = $this->createMock(ExtensionConfiguration::class);
        $this->handlerMock = $this->createMock(RequestHandlerInterface::class);
    }

    private function createMiddleware(): MjmlMiddleware
    {
        return new MjmlMiddleware(
            $this->mjmlServiceMock,
            $this->responseFactoryMock,
            $this->streamFactoryMock,
            $this->extensionConfigurationMock,
        );
    }

    private function createRequest(string $path, string $method = 'POST', string $body = ''): ServerRequestInterface
    {
        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn($path);

        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')->willReturn($body);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getUri')->willReturn($uri);
        $request->method('getMethod')->willReturn($method);
        $request->method('getBody')->willReturn($stream);

        return $request;
    }

    #[Test]
    public function nonMjmlPathIsPassedToNextHandler(): void
    {
        $request = $this->createRequest('/some/other/path');
        $expectedResponse = $this->createMock(ResponseInterface::class);
        $this->handlerMock->expects(self::once())->method('handle')->willReturn($expectedResponse);

        $middleware = $this->createMiddleware();
        $result = $middleware->process($request, $this->handlerMock);

        self::assertSame($expectedResponse, $result);
    }

    #[Test]
    public function mjmlEndpointIsPassedToNextHandlerWhenMiddlewareDisabled(): void
    {
        $this->extensionConfigurationMock
            ->method('get')
            ->willReturn(['enableMiddleware' => '0', 'binaryPath' => '']);

        $request = $this->createRequest('/_mjml/convert');
        $expectedResponse = $this->createMock(ResponseInterface::class);
        $this->handlerMock->expects(self::once())->method('handle')->willReturn($expectedResponse);

        $middleware = $this->createMiddleware();
        $result = $middleware->process($request, $this->handlerMock);

        self::assertSame($expectedResponse, $result);
    }

    #[Test]
    public function returnsMethodNotAllowedForGetRequest(): void
    {
        $this->extensionConfigurationMock
            ->method('get')
            ->willReturn(['enableMiddleware' => '1', 'binaryPath' => '']);

        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')->willReturn('');
        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('withHeader')->willReturnSelf();
        $responseMock->method('withBody')->willReturnSelf();
        $this->responseFactoryMock->method('createResponse')->with(405)->willReturn($responseMock);
        $this->streamFactoryMock->method('createStream')->willReturn($stream);

        $request = $this->createRequest('/_mjml/convert', 'GET');
        $middleware = $this->createMiddleware();
        $result = $middleware->process($request, $this->handlerMock);

        self::assertSame($responseMock, $result);
    }

    #[Test]
    public function returnsBadRequestForEmptyBody(): void
    {
        $this->extensionConfigurationMock
            ->method('get')
            ->willReturn(['enableMiddleware' => '1', 'binaryPath' => '']);

        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')->willReturn('');
        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('withHeader')->willReturnSelf();
        $responseMock->method('withBody')->willReturnSelf();
        $this->responseFactoryMock->method('createResponse')->with(400)->willReturn($responseMock);
        $this->streamFactoryMock->method('createStream')->willReturn($stream);

        $request = $this->createRequest('/_mjml/convert', 'POST', '   ');
        $middleware = $this->createMiddleware();
        $result = $middleware->process($request, $this->handlerMock);

        self::assertSame($responseMock, $result);
    }

    #[Test]
    public function returnsHtmlOnSuccessfulConversion(): void
    {
        $this->extensionConfigurationMock
            ->method('get')
            ->willReturn(['enableMiddleware' => '1', 'binaryPath' => '']);

        $this->mjmlServiceMock
            ->method('convert')
            ->willReturn('<html><body>Hello</body></html>');

        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')->willReturn('');
        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('withHeader')->willReturnSelf();
        $responseMock->method('withBody')->willReturnSelf();
        $this->responseFactoryMock->method('createResponse')->with(200)->willReturn($responseMock);
        $this->streamFactoryMock->method('createStream')->willReturn($stream);

        $request = $this->createRequest('/_mjml/convert', 'POST', '<mjml></mjml>');
        $middleware = $this->createMiddleware();
        $result = $middleware->process($request, $this->handlerMock);

        self::assertSame($responseMock, $result);
    }

    #[Test]
    public function returnsUnprocessableEntityWhenConversionFails(): void
    {
        $this->extensionConfigurationMock
            ->method('get')
            ->willReturn(['enableMiddleware' => '1', 'binaryPath' => '']);

        $this->mjmlServiceMock
            ->method('convert')
            ->willThrowException(new MjmlException('Conversion error'));

        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')->willReturn('');
        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('withHeader')->willReturnSelf();
        $responseMock->method('withBody')->willReturnSelf();
        $this->responseFactoryMock->method('createResponse')->with(422)->willReturn($responseMock);
        $this->streamFactoryMock->method('createStream')->willReturn($stream);

        $request = $this->createRequest('/_mjml/convert', 'POST', '<bad-mjml>');
        $middleware = $this->createMiddleware();
        $result = $middleware->process($request, $this->handlerMock);

        self::assertSame($responseMock, $result);
    }
}
