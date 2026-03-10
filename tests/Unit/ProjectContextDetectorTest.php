<?php

namespace RectorUi\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RectorUi\ProjectContextDetector;

class ProjectContextDetectorTest extends TestCase
{
    /** @var string[] */
    private $directories = array();

    protected function tearDown(): void
    {
        foreach ($this->directories as $directory) {
            $this->removeDirectory($directory);
        }
    }

    public function test_it_detects_basic_project_readiness(): void
    {
        $directory = $this->createProjectDirectory();
        mkdir($directory . '/vendor', 0777, true);
        mkdir($directory . '/vendor/bin', 0777, true);
        file_put_contents($directory . '/composer.json', "{}\n");
        file_put_contents($directory . '/vendor/bin/rector', "#!/usr/bin/env php\n");
        file_put_contents($directory . '/rector.php', "<?php\n");

        $detector = new ProjectContextDetector($directory, $this->gitRunner(true, ''));
        $context = $detector->detect();

        $this->assertSame($directory, $context->getPath());
        $this->assertTrue($context->isGitRepo());
        $this->assertSame('clean', $context->getGitStatus());
        $this->assertTrue($context->hasComposerJson());
        $this->assertTrue($context->hasRectorBinary());
        $this->assertTrue($context->hasRectorConfig());
        $this->assertSame($directory . '/rector.php', $context->getRectorConfigPath());
    }

    public function test_it_falls_back_to_rector_php_dist(): void
    {
        $directory = $this->createProjectDirectory();
        file_put_contents($directory . '/rector.php.dist', "<?php\n");

        $detector = new ProjectContextDetector($directory, $this->gitRunner(false, ''));
        $context = $detector->detect();

        $this->assertFalse($context->hasComposerJson());
        $this->assertFalse($context->hasRectorBinary());
        $this->assertTrue($context->hasRectorConfig());
        $this->assertSame($directory . '/rector.php.dist', $context->getRectorConfigPath());
    }

    public function test_it_reports_missing_project_files(): void
    {
        $directory = $this->createProjectDirectory();

        $detector = new ProjectContextDetector($directory, $this->gitRunner(false, ''));
        $context = $detector->detect();

        $this->assertFalse($context->isGitRepo());
        $this->assertSame('unknown', $context->getGitStatus());
        $this->assertFalse($context->hasComposerJson());
        $this->assertFalse($context->hasRectorBinary());
        $this->assertFalse($context->hasRectorConfig());
        $this->assertNull($context->getRectorConfigPath());
    }

    public function test_it_reports_dirty_git_status(): void
    {
        $directory = $this->createProjectDirectory();

        $detector = new ProjectContextDetector($directory, $this->gitRunner(true, " M composer.json\n"));
        $context = $detector->detect();

        $this->assertTrue($context->isGitRepo());
        $this->assertSame('dirty', $context->getGitStatus());
    }

    public function test_it_reports_unknown_git_status_when_status_command_fails(): void
    {
        $directory = $this->createProjectDirectory();

        $detector = new ProjectContextDetector($directory, function ($command, $workingDirectory) use ($directory) {
            $this->assertSame($directory, $workingDirectory);

            if ($command === 'git rev-parse --is-inside-work-tree') {
                return array(true, "true\n");
            }

            return array(false, '');
        });

        $context = $detector->detect();

        $this->assertTrue($context->isGitRepo());
        $this->assertSame('unknown', $context->getGitStatus());
    }

    /**
     * @return callable(string, string): array{0: bool, 1: string}
     */
    private function gitRunner(bool $isGitRepo, string $statusOutput): callable
    {
        return function ($command, $workingDirectory) use ($isGitRepo, $statusOutput) {
            $this->assertIsString($workingDirectory);

            if ($command === 'git rev-parse --is-inside-work-tree') {
                if ($isGitRepo) {
                    return array(true, "true\n");
                }

                return array(false, '');
            }

            if ($command === 'git status --porcelain') {
                return array(true, $statusOutput);
            }

            return array(false, '');
        };
    }

    private function createProjectDirectory(): string
    {
        $directory = sys_get_temp_dir() . '/rector-ui-project-' . uniqid('', true);
        mkdir($directory, 0777, true);
        $resolvedDirectory = realpath($directory) ?: $directory;
        $this->directories[] = $resolvedDirectory;

        return $resolvedDirectory;
    }

    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $items = scandir($directory);

        if (! is_array($items)) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . '/' . $item;

            if (is_dir($path)) {
                $this->removeDirectory($path);
                continue;
            }

            @unlink($path);
        }

        @rmdir($directory);
    }
}
