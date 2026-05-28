<?php

declare(strict_types=1);

namespace Maispace\MaiMjml\Runner;

use Maispace\MaiMjml\Exception\MjmlRenderException;

/**
 * Executes the MJML CLI binary via proc_open(), passing the MJML template as stdin.
 *
 * The binary must support the --stdin flag:
 *   mjml --stdin    reads MJML from stdin, writes rendered HTML to stdout
 *
 * Pipe reads time out after DEFAULT_TIMEOUT_SECONDS to prevent the PHP process
 * from hanging indefinitely when the MJML binary hangs or takes too long.
 */
final class MjmlProcessRunner implements MjmlProcessRunnerInterface
{
    private const int ERROR_CODE_PROCESS_START = 1748200001;
    private const int ERROR_CODE_PIPE_TIMEOUT = 1748200002;

    private const int DEFAULT_TIMEOUT_SECONDS = 60;

    /**
     * @return array{0: int, 1: string, 2: string} Tuple: [exitCode, stdout, stderr]
     *
     * @throws MjmlRenderException when the process cannot be started or times out
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

        foreach ([1, 2] as $i) {
            stream_set_timeout($pipes[$i], self::DEFAULT_TIMEOUT_SECONDS);
        }

        $output = (string) stream_get_contents($pipes[1]);
        $metaStdout = stream_get_meta_data($pipes[1]);
        fclose($pipes[1]);

        $errorOutput = (string) stream_get_contents($pipes[2]);
        $metaStderr = stream_get_meta_data($pipes[2]);
        fclose($pipes[2]);

        if ($metaStdout['timed_out'] || $metaStderr['timed_out']) {
            $this->terminateProcess($process);
            throw new MjmlRenderException(
                sprintf(
                    'MJML process timed out after %d seconds. Large MJML templates may need more time.',
                    self::DEFAULT_TIMEOUT_SECONDS,
                ),
                self::ERROR_CODE_PIPE_TIMEOUT,
            );
        }

        $exitCode = proc_close($process);

        return [$exitCode, $output, $errorOutput];
    }

    private function terminateProcess($process): void
    {
        proc_terminate($process, 15);
        usleep(100_000);
        if (proc_get_status($process)['running']) {
            proc_terminate($process, 9);
        }
        proc_close($process);
    }
}
