<?php

declare(strict_types=1);

namespace Maispace\MaiMjml\Service;

use Maispace\MaiMjml\Exception\MjmlRenderException;
use Maispace\MaiMjml\Runner\MjmlProcessRunnerInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

/**
 * Renders MJML templates to responsive HTML by invoking the configured MJML CLI binary.
 *
 * This service is the sole public API of mai_mjml. Downstream extensions (mai_mail)
 * inject it as a suggested dependency — feature extensions must not depend on it directly.
 *
 * Usage:
 *   $html = $mjmlRendererService->render($mjmlContent);
 *
 * Configure the binary path in Extension Manager → mai_mjml → Binary path.
 */
final class MjmlRendererService
{
    private const int ERROR_CODE_EMPTY_BINARY_PATH = 1748200010;
    private const int ERROR_CODE_RENDER_FAILED = 1748200011;

    public function __construct(
        private readonly MjmlProcessRunnerInterface $processRunner,
        private readonly ExtensionConfiguration $extensionConfiguration,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Converts an MJML template string to responsive HTML.
     *
     * @throws MjmlRenderException when the binary is not configured or the process fails
     */
    public function render(string $mjmlContent): string
    {
        $binaryPath = $this->getBinaryPath();

        if ($binaryPath === '') {
            $this->logger->error('MJML binary path is not configured');
            throw new MjmlRenderException(
                'MJML binary path is not configured. Set it in Extension Manager → mai_mjml → Binary path.',
                self::ERROR_CODE_EMPTY_BINARY_PATH,
            );
        }

        try {
            [$exitCode, $output, $errorOutput] = $this->processRunner->run($binaryPath, $mjmlContent);
        } catch (MjmlRenderException $e) {
            $this->logger->error('MJML process execution failed', [
                'exception' => $e,
                'binaryPath' => $binaryPath,
                'mjmlLength' => strlen($mjmlContent),
            ]);
            throw $e;
        }

        if ($exitCode !== 0) {
            $this->logger->error('MJML rendering failed with non-zero exit code', [
                'exitCode' => $exitCode,
                'errorOutput' => $errorOutput,
                'binaryPath' => $binaryPath,
                'mjmlLength' => strlen($mjmlContent),
            ]);
            throw new MjmlRenderException(
                sprintf('MJML rendering failed (exit code %d): %s', $exitCode, $errorOutput),
                self::ERROR_CODE_RENDER_FAILED,
            );
        }

        return $output;
    }

    /**
     * Returns the configured MJML binary path from the extension settings.
     * Returns an empty string when the setting is absent or the configuration throws.
     */
    public function getBinaryPath(): string
    {
        try {
            $config = $this->extensionConfiguration->get('mai_mjml');
        } catch (\Exception) {
            $config = [];
        }

        if (!is_array($config)) {
            $config = [];
        }

        return (string) ($config['binaryPath'] ?? '');
    }
}
