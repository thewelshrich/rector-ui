<?php

namespace RectorUi;

class ProjectContextDetector
{
    /** @var string */
    private $workingDirectory;

    /** @var callable */
    private $commandRunner;

    /**
     * @param callable(string, string): array{0: bool, 1: string}|null $commandRunner
     */
    public function __construct(?string $workingDirectory = null, ?callable $commandRunner = null)
    {
        $resolvedWorkingDirectory = $workingDirectory ?: getcwd();
        $realPath = realpath($resolvedWorkingDirectory);
        $this->workingDirectory = $realPath ?: $resolvedWorkingDirectory;
        $this->commandRunner = $commandRunner ?: function ($command, $workingDirectory) {
            return $this->runCommand($command, $workingDirectory);
        };
    }

    public function detect(): ProjectContext
    {
        $path = $this->workingDirectory;
        $composerJsonPath = $path . '/composer.json';
        $rectorBinaryPath = $path . '/vendor/bin/rector';
        $rectorConfigPath = $this->detectRectorConfigPath($path);
        $isGitRepo = $this->isGitRepository($path);
        $gitStatus = 'unknown';

        if ($isGitRepo) {
            $gitStatus = $this->detectGitStatus($path);
        }

        return new ProjectContext(
            $path,
            $isGitRepo,
            $gitStatus,
            file_exists($composerJsonPath),
            file_exists($rectorBinaryPath),
            $rectorConfigPath !== null,
            $rectorConfigPath,
            file_exists($rectorBinaryPath) && $rectorConfigPath !== null
        );
    }

    private function detectRectorConfigPath(string $path): ?string
    {
        $candidates = array(
            $path . '/rector.php',
            $path . '/rector.php.dist',
        );

        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function isGitRepository(string $path): bool
    {
        [$success, $output] = call_user_func($this->commandRunner, 'git rev-parse --is-inside-work-tree', $path);

        return $success && trim($output) === 'true';
    }

    private function detectGitStatus(string $path): string
    {
        [$success, $output] = call_user_func($this->commandRunner, 'git status --porcelain', $path);

        if (! $success) {
            return 'unknown';
        }

        if (trim($output) === '') {
            return 'clean';
        }

        return 'dirty';
    }

    /**
     * @return array{0: bool, 1: string}
     */
    private function runCommand(string $command, string $workingDirectory): array
    {
        $fullCommand = sprintf(
            'cd %s && %s 2>/dev/null',
            escapeshellarg($workingDirectory),
            $command
        );

        $output = array();
        $exitCode = 1;
        exec($fullCommand, $output, $exitCode);

        return array($exitCode === 0, implode("\n", $output));
    }
}
