<?php

declare(strict_types=1);

namespace Maispace\MaiMjml\Tests\Unit\Service;

use Maispace\MaiMjml\Exception\MjmlRenderException;
use Maispace\MaiMjml\Runner\MjmlProcessRunnerInterface;
use Maispace\MaiMjml\Service\MjmlRendererService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

final class MjmlRendererServiceTest extends TestCase
{
    private MjmlProcessRunnerInterface&MockObject $processRunner;
    private ExtensionConfiguration&MockObject $extensionConfiguration;
    private LoggerInterface&MockObject $logger;
    private MjmlRendererService $service;

    protected function setUp(): void
    {
        $this->processRunner = $this->createMock(MjmlProcessRunnerInterface::class);
        $this->extensionConfiguration = $this->createMock(ExtensionConfiguration::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new MjmlRendererService(
            $this->processRunner,
            $this->extensionConfiguration,
            $this->logger,
        );
    }

    // ── render() — happy path ─────────────────────────────────────────────────

    #[Test]
    public function renderReturnsHtmlOnSuccess(): void
    {
        $this->extensionConfiguration->method('get')
            ->with('mai_mjml')
            ->willReturn(['binaryPath' => '/usr/local/bin/mjml']);

        $this->processRunner->method('run')
            ->willReturn([0, '<html><body><p>Hello</p></body></html>', '']);

        $result = $this->service->render('<mjml><mj-body></mj-body></mjml>');

        self::assertSame('<html><body><p>Hello</p></body></html>', $result);
    }

    #[Test]
    public function renderPassesMjmlContentToRunner(): void
    {
        $mjmlContent = '<mjml><mj-body><mj-section><mj-column><mj-text>Hello</mj-text></mj-column></mj-section></mj-body></mjml>';

        $this->extensionConfiguration->method('get')
            ->with('mai_mjml')
            ->willReturn(['binaryPath' => '/usr/local/bin/mjml']);

        $this->processRunner->expects(self::once())
            ->method('run')
            ->with('/usr/local/bin/mjml', $mjmlContent)
            ->willReturn([0, '<html></html>', '']);

        $this->service->render($mjmlContent);
    }

    #[Test]
    public function renderPassesBinaryPathToRunner(): void
    {
        $this->extensionConfiguration->method('get')
            ->with('mai_mjml')
            ->willReturn(['binaryPath' => '/custom/path/to/mjml']);

        $this->processRunner->expects(self::once())
            ->method('run')
            ->with('/custom/path/to/mjml', self::anything())
            ->willReturn([0, '<html></html>', '']);

        $this->service->render('<mjml></mjml>');
    }

    // ── render() — error handling ─────────────────────────────────────────────

    #[Test]
    public function renderThrowsWhenBinaryPathIsEmpty(): void
    {
        $this->extensionConfiguration->method('get')
            ->with('mai_mjml')
            ->willReturn(['binaryPath' => '']);

        $this->processRunner->expects(self::never())->method('run');

        $this->expectException(MjmlRenderException::class);
        $this->expectExceptionCode(1748200010);

        $this->service->render('<mjml></mjml>');
    }

    #[Test]
    public function renderThrowsWhenBinaryPathKeyIsMissing(): void
    {
        $this->extensionConfiguration->method('get')
            ->with('mai_mjml')
            ->willReturn([]);

        $this->processRunner->expects(self::never())->method('run');

        $this->expectException(MjmlRenderException::class);
        $this->expectExceptionCode(1748200010);

        $this->service->render('<mjml></mjml>');
    }

    #[Test]
    public function renderThrowsWhenRunnerReturnsNonZeroExitCode(): void
    {
        $this->extensionConfiguration->method('get')
            ->with('mai_mjml')
            ->willReturn(['binaryPath' => '/usr/local/bin/mjml']);

        $this->processRunner->method('run')
            ->willReturn([1, '', 'SyntaxError: Unexpected token']);

        $this->expectException(MjmlRenderException::class);
        $this->expectExceptionCode(1748200011);
        $this->expectExceptionMessageMatches('/exit code 1/');
        $this->expectExceptionMessageMatches('/SyntaxError/');

        $this->service->render('<mjml><invalid></invalid></mjml>');
    }

    #[Test]
    public function renderThrowsWhenExtensionConfigurationThrows(): void
    {
        $this->extensionConfiguration->method('get')
            ->willThrowException(new \RuntimeException('Config not found'));

        $this->processRunner->expects(self::never())->method('run');

        $this->expectException(MjmlRenderException::class);
        $this->expectExceptionCode(1748200010);

        $this->service->render('<mjml></mjml>');
    }

    // ── getBinaryPath() ───────────────────────────────────────────────────────

    #[Test]
    public function getBinaryPathReturnsConfiguredPath(): void
    {
        $this->extensionConfiguration->method('get')
            ->with('mai_mjml')
            ->willReturn(['binaryPath' => '/usr/bin/mjml']);

        self::assertSame('/usr/bin/mjml', $this->service->getBinaryPath());
    }

    #[Test]
    public function getBinaryPathReturnsEmptyStringWhenKeyMissing(): void
    {
        $this->extensionConfiguration->method('get')
            ->with('mai_mjml')
            ->willReturn([]);

        self::assertSame('', $this->service->getBinaryPath());
    }

    #[Test]
    public function getBinaryPathReturnsEmptyStringOnConfigException(): void
    {
        $this->extensionConfiguration->method('get')
            ->willThrowException(new \RuntimeException('Extension not configured'));

        self::assertSame('', $this->service->getBinaryPath());
    }

    #[Test]
    public function getBinaryPathReturnsEmptyStringWhenConfigIsNotArray(): void
    {
        $this->extensionConfiguration->method('get')
            ->with('mai_mjml')
            ->willReturn('invalid-string');

        self::assertSame('', $this->service->getBinaryPath());
    }

    // ── Exception boundary logging ──────────────────────────────────────────

    #[Test]
    public function renderLogsErrorWhenBinaryPathIsEmpty(): void
    {
        $this->extensionConfiguration->method('get')
            ->with('mai_mjml')
            ->willReturn(['binaryPath' => '']);

        $this->logger->expects(self::once())
            ->method('error')
            ->with('MJML binary path is not configured');

        $this->expectException(MjmlRenderException::class);

        $this->service->render('<mjml></mjml>');
    }

    #[Test]
    public function renderLogsErrorWhenRunnerReturnsNonZeroExitCode(): void
    {
        $this->extensionConfiguration->method('get')
            ->with('mai_mjml')
            ->willReturn(['binaryPath' => '/usr/local/bin/mjml']);

        $this->processRunner->method('run')
            ->willReturn([1, '', 'SyntaxError: Unexpected token']);

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'MJML rendering failed with non-zero exit code',
                self::callback(function (array $context): bool {
                    return ($context['exitCode'] ?? null) === 1
                        && ($context['errorOutput'] ?? '') === 'SyntaxError: Unexpected token'
                        && ($context['binaryPath'] ?? '') === '/usr/local/bin/mjml'
                        && ($context['mjmlLength'] ?? 0) > 0;
                }),
            );

        $this->expectException(MjmlRenderException::class);
        $this->expectExceptionCode(1748200011);

        $this->service->render('<mjml><invalid></invalid></mjml>');
    }

    #[Test]
    public function renderLogsErrorWhenRunnerThrowsException(): void
    {
        $this->extensionConfiguration->method('get')
            ->with('mai_mjml')
            ->willReturn(['binaryPath' => '/usr/local/bin/mjml']);

        $runnerException = new MjmlRenderException(
            'MJML process timed out after 60 seconds',
            1748200002,
        );

        $this->processRunner->method('run')
            ->willThrowException($runnerException);

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'MJML process execution failed',
                self::callback(function (array $context) use ($runnerException): bool {
                    return ($context['exception'] ?? null) === $runnerException
                        && ($context['binaryPath'] ?? '') === '/usr/local/bin/mjml'
                        && ($context['mjmlLength'] ?? 0) > 0;
                }),
            );

        $this->expectException(MjmlRenderException::class);
        $this->expectExceptionCode(1748200002);
        $this->expectExceptionMessage('MJML process timed out after 60 seconds');

        $this->service->render('<mjml></mjml>');
    }
}
