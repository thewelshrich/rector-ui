<?php

namespace RectorUi\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RectorUi\ProjectContext;
use RectorUi\RectorAnalysisService;

class RectorAnalysisServiceTest extends TestCase
{
    public function test_it_reports_unavailable_when_rector_binary_is_missing(): void
    {
        $context = new ProjectContext('/tmp/project', true, 'clean', true, false, true, '/tmp/project/rector.php');

        $service = new RectorAnalysisService(function () use ($context) {
            return $context;
        });

        $result = $service->runDryRun();

        $this->assertFalse($result->isAvailable());
        $this->assertSame('unavailable', $result->getStatus());
        $this->assertSame('', $result->getCommand());
        $this->assertNull($result->getChangedFilesCount());
    }

    public function test_it_reports_unavailable_when_rector_config_is_missing(): void
    {
        $context = new ProjectContext('/tmp/project', true, 'clean', true, true, false, null);

        $service = new RectorAnalysisService(function () use ($context) {
            return $context;
        });

        $result = $service->runDryRun();

        $this->assertFalse($result->isAvailable());
        $this->assertSame('unavailable', $result->getStatus());
    }

    public function test_it_reports_success_and_parses_changed_file_count(): void
    {
        $context = new ProjectContext('/tmp/project', true, 'clean', true, true, true, '/tmp/project/rector.php');
        $json = json_encode(array(
            'totals' => array(
                'changed_files' => 4,
            ),
        ));

        $service = new RectorAnalysisService(
            function () use ($context) {
                return $context;
            },
            function ($command, $workingDirectory) use ($json) {
                $this->assertSame('vendor/bin/rector process --dry-run --output-format=json', $command);
                $this->assertSame('/tmp/project', $workingDirectory);

                return array(0, $json, '');
            }
        );

        $result = $service->runDryRun();

        $this->assertTrue($result->isAvailable());
        $this->assertSame('success', $result->getStatus());
        $this->assertSame(0, $result->getExitCode());
        $this->assertSame(4, $result->getChangedFilesCount());
        $this->assertSame($json, $result->getStdout());
        $this->assertSame('', $result->getStderr());
    }

    public function test_it_reports_failure_when_command_exits_non_zero(): void
    {
        $context = new ProjectContext('/tmp/project', true, 'clean', true, true, true, '/tmp/project/rector.php');

        $service = new RectorAnalysisService(
            function () use ($context) {
                return $context;
            },
            function () {
                return array(1, '', 'Rector crashed');
            }
        );

        $result = $service->runDryRun();

        $this->assertTrue($result->isAvailable());
        $this->assertSame('failure', $result->getStatus());
        $this->assertSame(1, $result->getExitCode());
        $this->assertSame('Rector crashed', $result->getStderr());
    }

    public function test_it_keeps_changed_files_count_null_for_malformed_json(): void
    {
        $context = new ProjectContext('/tmp/project', true, 'clean', true, true, true, '/tmp/project/rector.php');

        $service = new RectorAnalysisService(
            function () use ($context) {
                return $context;
            },
            function () {
                return array(0, '{bad json', '');
            }
        );

        $result = $service->runDryRun();

        $this->assertSame('success', $result->getStatus());
        $this->assertNull($result->getChangedFilesCount());
    }
}
