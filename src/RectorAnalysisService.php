<?php

namespace RectorUi;

class RectorAnalysisService
{
    /** @var callable */
    private $projectContextProvider;

    /** @var callable */
    private $commandRunner;

    /**
     * @param callable(): ProjectContext|null $projectContextProvider
     * @param callable(string, string): array{0: int, 1: string, 2: string}|null $commandRunner
     */
    public function __construct($projectContextProvider = null, $commandRunner = null)
    {
        $detector = new ProjectContextDetector();
        $this->projectContextProvider = $projectContextProvider ?: function () use ($detector) {
            return $detector->detect();
        };
        $this->commandRunner = $commandRunner ?: function ($command, $workingDirectory) {
            return $this->runCommand($command, $workingDirectory);
        };
    }

    public function isAvailable(ProjectContext $context): bool
    {
        return $context->hasRectorBinary() && $context->hasRectorConfig();
    }

    public function runDryRun(): RectorAnalysisResult
    {
        /** @var ProjectContext $context */
        $context = call_user_func($this->projectContextProvider);

        if (! $this->isAvailable($context)) {
            return new RectorAnalysisResult(false, 'unavailable', '', null, null, '', '');
        }

        $command = 'vendor/bin/rector process --dry-run --output-format=json';
        list($exitCode, $stdout, $stderr) = call_user_func($this->commandRunner, $command, $context->getPath());

        return new RectorAnalysisResult(
            true,
            $exitCode === 0 ? 'success' : 'failure',
            $command,
            $exitCode,
            $this->parseChangedFilesCount($stdout),
            $stdout,
            $stderr
        );
    }

    private function parseChangedFilesCount(string $stdout): ?int
    {
        $decoded = json_decode($stdout, true);

        if (! is_array($decoded)) {
            return null;
        }

        if (! isset($decoded['totals']) || ! is_array($decoded['totals'])) {
            return null;
        }

        if (! isset($decoded['totals']['changed_files'])) {
            return null;
        }

        return (int) $decoded['totals']['changed_files'];
    }

    /**
     * @return array{0: int, 1: string, 2: string}
     */
    private function runCommand(string $command, string $workingDirectory): array
    {
        $descriptorSpec = array(
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w'),
        );

        $process = proc_open($command, $descriptorSpec, $pipes, $workingDirectory);

        if (! is_resource($process)) {
            return array(1, '', 'Unable to start Rector process');
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        return array((int) $exitCode, (string) $stdout, (string) $stderr);
    }
}
