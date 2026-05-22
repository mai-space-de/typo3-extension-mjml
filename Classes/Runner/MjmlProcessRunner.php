<?php

declare(strict_types=1);

namespace Maispace\MaiMjml\Runner;

use Maispace\MaiMjml\Exception\MjmlRenderException;

/**
 * Executes the MJML CLI binary via proc_open(), passing the MJML template as stdin.
 *
 * The binary must support the --stdin flag:
 *   mjml --stdin    reads MJML from stdin, writes rendered HTML to stdout
 */
final class MjmlProcessRunner implements MjmlProcessRunnerInterface
{
    private const int ERROR_CODE_PROCESS_START = 1748200001;

    /**
     * @return array{0: int, 1: string, 2: string} Tuple: [exitCode, stdout, stderr]
     *
     * @throws MjmlRenderException when the process cannot be started
     */
    public function run(string $binaryPath, string $mjmlContent): array
    {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open([$binaryPath, '--stdin'], $descriptors, $pipes);

        if (!is_resource($process)) {
            throw new MjmlRenderException(
                'Failed to start the MJML process. Is the binary executable at "' . $binaryPath . '"?',
                self::ERROR_CODE_PROCESS_START,
            );
        }

        fwrite($pipes[0], $mjmlContent);
        fclose($pipes[0]);

        $output = (string) stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $errorOutput = (string) stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        return [$exitCode, $output, $errorOutput];
    }
}
