<?php

declare(strict_types=1);

namespace Maispace\MaiMjml\Middleware;

use Maispace\MaiMjml\Exception\MjmlException;
use Maispace\MaiMjml\Service\MjmlService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

/**
 * HTTP middleware exposing the MJML conversion service via a simple JSON API.
 *
 * Endpoint: POST /_mjml/convert
 *   Request body : raw MJML markup (text/plain or application/xml)
 *   Success (200): { "html": "<html>…</html>" }
 *   Error   (4xx): { "error": "…reason…" }
 *
 * The middleware must be enabled via the extension configuration
 * (TYPO3 Extension Manager → MJML → enableMiddleware = 1) and is
 * disabled by default for security reasons.
 */
final class MjmlMiddleware implements MiddlewareInterface
{
    private const ENDPOINT_PATH = '/_mjml/convert';

    public function __construct(
        private readonly MjmlService $mjmlService,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly ExtensionConfiguration $extensionConfiguration,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getUri()->getPath() !== self::ENDPOINT_PATH) {
            return $handler->handle($request);
        }

        if (!$this->isMiddlewareEnabled()) {
            return $handler->handle($request);
        }

        if ($request->getMethod() !== 'POST') {
            return $this->jsonResponse(405, ['error' => 'Method Not Allowed – use POST']);
        }

        $body = (string) $request->getBody();
        if (trim($body) === '') {
            return $this->jsonResponse(400, ['error' => 'Request body must not be empty']);
        }

        try {
            $html = $this->mjmlService->convert($body);
            return $this->jsonResponse(200, ['html' => $html]);
        } catch (MjmlException $e) {
            return $this->jsonResponse(422, ['error' => $e->getMessage()]);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function jsonResponse(int $statusCode, array $data): ResponseInterface
    {
        $json = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $body = $this->streamFactory->createStream($json);

        return $this->responseFactory
            ->createResponse($statusCode)
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withBody($body);
    }

    private function isMiddlewareEnabled(): bool
    {
        try {
            /** @var array<string, string> $config */
            $config = $this->extensionConfiguration->get('mai_mjml');

            return !empty($config['enableMiddleware']);
        } catch (\Throwable) {
            return false;
        }
    }
}
