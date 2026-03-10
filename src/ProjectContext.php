<?php

namespace RectorUi;

final class ProjectContext
{
    /** @var string */
    private $path;

    /** @var bool */
    private $isGitRepo;

    /** @var string */
    private $gitStatus;

    /** @var bool */
    private $hasComposerJson;

    /** @var bool */
    private $hasRectorBinary;

    /** @var bool */
    private $hasRectorConfig;

    /** @var string|null */
    private $rectorConfigPath;

    /** @var bool */
    private $hasRectorAnalysis;

    public function __construct(
        string $path,
        bool $isGitRepo,
        string $gitStatus,
        bool $hasComposerJson,
        bool $hasRectorBinary,
        bool $hasRectorConfig,
        ?string $rectorConfigPath,
        bool $hasRectorAnalysis = false
    ) {
        $this->path = $path;
        $this->isGitRepo = $isGitRepo;
        $this->gitStatus = $gitStatus;
        $this->hasComposerJson = $hasComposerJson;
        $this->hasRectorBinary = $hasRectorBinary;
        $this->hasRectorConfig = $hasRectorConfig;
        $this->rectorConfigPath = $rectorConfigPath;
        $this->hasRectorAnalysis = $hasRectorAnalysis;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function isGitRepo(): bool
    {
        return $this->isGitRepo;
    }

    public function getGitStatus(): string
    {
        return $this->gitStatus;
    }

    public function hasComposerJson(): bool
    {
        return $this->hasComposerJson;
    }

    public function hasRectorBinary(): bool
    {
        return $this->hasRectorBinary;
    }

    public function hasRectorConfig(): bool
    {
        return $this->hasRectorConfig;
    }

    public function getRectorConfigPath(): ?string
    {
        return $this->rectorConfigPath;
    }

    public function hasRectorAnalysis(): bool
    {
        return $this->hasRectorAnalysis;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array(
            'path' => $this->path,
            'isGitRepo' => $this->isGitRepo,
            'gitStatus' => $this->gitStatus,
            'hasComposerJson' => $this->hasComposerJson,
            'hasRectorBinary' => $this->hasRectorBinary,
            'hasRectorConfig' => $this->hasRectorConfig,
            'rectorConfigPath' => $this->rectorConfigPath,
            'hasRectorAnalysis' => $this->hasRectorAnalysis,
        );
    }
}
