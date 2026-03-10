<?php

namespace RectorUi;

final class RectorAnalysisResult
{
    /** @var bool */
    private $available;

    /** @var string */
    private $status;

    /** @var string */
    private $command;

    /** @var int|null */
    private $exitCode;

    /** @var int|null */
    private $changedFilesCount;

    /** @var string */
    private $stdout;

    /** @var string */
    private $stderr;

    public function __construct(
        bool $available,
        string $status,
        string $command,
        ?int $exitCode,
        ?int $changedFilesCount,
        string $stdout,
        string $stderr
    ) {
        $this->available = $available;
        $this->status = $status;
        $this->command = $command;
        $this->exitCode = $exitCode;
        $this->changedFilesCount = $changedFilesCount;
        $this->stdout = $stdout;
        $this->stderr = $stderr;
    }

    public function isAvailable(): bool
    {
        return $this->available;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCommand(): string
    {
        return $this->command;
    }

    public function getExitCode(): ?int
    {
        return $this->exitCode;
    }

    public function getChangedFilesCount(): ?int
    {
        return $this->changedFilesCount;
    }

    public function getStdout(): string
    {
        return $this->stdout;
    }

    public function getStderr(): string
    {
        return $this->stderr;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array(
            'available' => $this->available,
            'status' => $this->status,
            'command' => $this->command,
            'exitCode' => $this->exitCode,
            'changedFilesCount' => $this->changedFilesCount,
            'stdout' => $this->stdout,
            'stderr' => $this->stderr,
        );
    }
}
