<?php

declare(strict_types=1);

namespace Maispace\MaiMjml\Runner;

/**
 * Abstraction over the MJML CLI process execution.
 *
 * Extracted for testability: inject the real implementation in production,
 * use a mock in unit tests.
 */
interface MjmlProcessRunnerInterface
{
    /**
     * Runs the MJML binary with the given MJML content as stdin input.
     *
     * @return array{0: int, 1: string, 2: string} Tuple: [exitCode, stdout, stderr]
     */
    public function run(string $binaryPath, string $mjmlContent): array;
}
