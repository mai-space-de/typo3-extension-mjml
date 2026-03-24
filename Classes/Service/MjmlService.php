<?php

declare(strict_types=1);

namespace Maispace\MaiMjml\Service;

use Maispace\MaiMjml\Exception\MjmlException;
use Symfony\Component\Process\Process;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

/**
 * Service for converting MJML markup to HTML using the MJML binary.
 *
 * The MJML binary is resolved in the following order:
 *   1. Extension configuration (TYPO3 Extension Manager → MJML → binaryPath)
 *   2. Environment variable MJML_BINARY
 *   3. Local node_modules installed inside this extension (node_modules/.bin/mjml)
 *   4. Global `mjml` binary on the system PATH
 */
class MjmlService
{
    private readonly string $binaryPath;

    public function __construct(private readonly ExtensionConfiguration $extensionConfiguration)
    {
        $this->binaryPath = $this->resolveBinaryPath();
    }

    /**
     * Convert MJML markup to responsive HTML.
     *
     * @param string $mjmlContent The MJML source markup.
     * @param array<string, string|int|bool> $options Additional options passed to the MJML binary
     *        via --config.<key> <value> (e.g. ['beautify' => true, 'minify' => false]).
     * @return string The resulting HTML.
     * @throws MjmlException If the conversion fails.
     */
    public function convert(string $mjmlContent, array $options = []): string
    {
        $tempInputFile = tempnam(sys_get_temp_dir(), 'mjml_input_');
        if ($tempInputFile === false) {
            throw new MjmlException('Could not create temporary input file for MJML conversion.', 1711300001);
        }

        $command = [$this->binaryPath, $tempInputFile, '--stdout'];
        foreach ($options as $key => $value) {
            $command[] = '--config.' . $key;
            $command[] = (string)$value;
        }
        $process = new Process($command);

        try {
            file_put_contents($tempInputFile, $mjmlContent);
            $process->run();
        } catch (\Throwable $e) {
            throw new MjmlException(
                sprintf('MJML process could not be started: %s', $e->getMessage()),
                1711300003,
                $e
            );
        } finally {
            if (file_exists($tempInputFile)) {
                unlink($tempInputFile);
            }
        }

        if (!$process->isSuccessful()) {
            throw new MjmlException(
                sprintf('MJML conversion failed: %s', trim($process->getErrorOutput())),
                1711300002
            );
        }

        return $process->getOutput();
    }

    /**
     * Check whether the MJML binary is available and executable.
     */
    public function isAvailable(): bool
    {
        try {
            $process = new Process([$this->binaryPath, '--version']);
            $process->run();

            return $process->isSuccessful();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Return the version string reported by the MJML binary (e.g. "4.15.3").
     * Returns "unknown" when the binary cannot be executed.
     */
    public function getVersion(): string
    {
        try {
            $process = new Process([$this->binaryPath, '--version']);
            $process->run();

            if (!$process->isSuccessful()) {
                return 'unknown';
            }

            return trim($process->getOutput());
        } catch (\Throwable) {
            return 'unknown';
        }
    }

    /**
     * Return the resolved absolute path to the MJML binary.
     */
    public function getBinaryPath(): string
    {
        return $this->binaryPath;
    }

    private function resolveBinaryPath(): string
    {
        // 1. Extension configuration
        try {
            /** @var array<string, string> $config */
            $config = $this->extensionConfiguration->get('mai_mjml');
            if (!empty($config['binaryPath'])) {
                return $config['binaryPath'];
            }
        } catch (\Throwable) {
            // Extension configuration not available – continue to next check.
        }

        // 2. Environment variable
        $envBinary = (string)getenv('MJML_BINARY');
        if ($envBinary !== '') {
            return $envBinary;
        }

        // 3. Local node_modules inside this extension
        $localBinary = dirname(__DIR__, 2) . '/node_modules/.bin/mjml';
        if (file_exists($localBinary)) {
            return $localBinary;
        }

        // 4. Fall back to the global binary on PATH
        return 'mjml';
    }
}
