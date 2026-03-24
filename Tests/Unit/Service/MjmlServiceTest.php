<?php

declare(strict_types=1);

namespace Maispace\MaiMjml\Tests\Unit\Service;

use Maispace\MaiMjml\Exception\MjmlException;
use Maispace\MaiMjml\Service\MjmlService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class MjmlServiceTest extends UnitTestCase
{
    private ExtensionConfiguration&MockObject $extensionConfigurationMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extensionConfigurationMock = $this->createMock(ExtensionConfiguration::class);
        // By default, pretend there is no extension configuration.
        $this->extensionConfigurationMock
            ->method('get')
            ->willThrowException(new \RuntimeException('No configuration'));
    }

    #[Test]
    public function isAvailableReturnsFalseWhenBinaryDoesNotExist(): void
    {
        putenv('MJML_BINARY=/nonexistent/path/to/mjml');
        $service = new MjmlService($this->extensionConfigurationMock);

        self::assertFalse($service->isAvailable());

        putenv('MJML_BINARY=');
    }

    #[Test]
    public function getVersionReturnsUnknownWhenBinaryDoesNotExist(): void
    {
        putenv('MJML_BINARY=/nonexistent/path/to/mjml');
        $service = new MjmlService($this->extensionConfigurationMock);

        self::assertSame('unknown', $service->getVersion());

        putenv('MJML_BINARY=');
    }

    #[Test]
    public function getBinaryPathReturnsEnvVariableWhenSet(): void
    {
        putenv('MJML_BINARY=/custom/mjml');
        $service = new MjmlService($this->extensionConfigurationMock);

        self::assertSame('/custom/mjml', $service->getBinaryPath());

        putenv('MJML_BINARY=');
    }

    #[Test]
    public function getBinaryPathReturnsConfiguredValueWhenSet(): void
    {
        $this->extensionConfigurationMock
            ->method('get')
            ->with('mai_mjml')
            ->willReturn(['binaryPath' => '/configured/mjml', 'enableMiddleware' => '0']);

        $service = new MjmlService($this->extensionConfigurationMock);

        self::assertSame('/configured/mjml', $service->getBinaryPath());
    }

    #[Test]
    public function getBinaryPathFallsBackToGlobalWhenNothingConfigured(): void
    {
        putenv('MJML_BINARY=');
        $service = new MjmlService($this->extensionConfigurationMock);

        // When nothing is configured and local node_modules are absent the
        // service falls back to the bare "mjml" command on the system PATH.
        self::assertSame('mjml', $service->getBinaryPath());
    }

    #[Test]
    public function convertThrowsMjmlExceptionWhenBinaryFails(): void
    {
        $this->expectException(MjmlException::class);

        putenv('MJML_BINARY=/nonexistent/path/to/mjml');
        $service = new MjmlService($this->extensionConfigurationMock);

        $service->convert('<mjml><mj-body></mj-body></mjml>');

        putenv('MJML_BINARY=');
    }
}
