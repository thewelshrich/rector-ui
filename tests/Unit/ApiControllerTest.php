<?php

namespace RectorUi\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RectorUi\ApiController;
use RectorUi\ProjectContext;
use RectorUi\ProjectContextDetector;
use RectorUi\RectorAnalysisResult;
use RectorUi\RectorAnalysisService;

class ApiControllerTest extends TestCase
{
    public function test_health_payload_contains_expected_fields(): void
    {
        $controller = new ApiController('0.1.0');

        $payload = json_decode($controller->healthJson(), true);

        $this->assertSame('ok', $payload['status']);
        $this->assertSame('0.1.0', $payload['appVersion']);
        $this->assertSame(PHP_VERSION, $payload['phpVersion']);
    }

    public function test_meta_payload_reports_rector_as_unavailable(): void
    {
        $controller = new ApiController('0.1.0');

        $payload = json_decode($controller->metaJson(), true);

        $this->assertSame('Rector UI', $payload['appName']);
        $this->assertSame('0.1.0', $payload['version']);
        $this->assertFalse($payload['capabilities']['rector']);
        $this->assertSame(PHP_OS_FAMILY, $payload['runtime']['os']);
        $this->assertSame(PHP_VERSION, $payload['runtime']['php']);
    }

    public function test_project_payload_contains_expected_fields(): void
    {
        $context = new ProjectContext(
            '/tmp/demo',
            true,
            'dirty',
            true,
            false,
            true,
            '/tmp/demo/rector.php'
        );

        $detector = $this->createMock(ProjectContextDetector::class);
        $detector->method('detect')->willReturn($context);

        $controller = new ApiController('0.1.0', null, $detector);
        $payload = json_decode($controller->projectJson(), true);

        $this->assertSame('ok', $payload['status']);
        $this->assertSame('/tmp/demo', $payload['path']);
        $this->assertTrue($payload['isGitRepo']);
        $this->assertSame('dirty', $payload['gitStatus']);
        $this->assertTrue($payload['hasComposerJson']);
        $this->assertFalse($payload['hasRectorBinary']);
        $this->assertTrue($payload['hasRectorConfig']);
        $this->assertSame('/tmp/demo/rector.php', $payload['rectorConfigPath']);
        $this->assertFalse($payload['hasRectorAnalysis']);
    }

    public function test_project_payload_reports_analysis_availability(): void
    {
        $context = new ProjectContext(
            '/tmp/demo',
            true,
            'clean',
            true,
            true,
            true,
            '/tmp/demo/rector.php',
            true
        );

        $detector = $this->createMock(ProjectContextDetector::class);
        $detector->method('detect')->willReturn($context);

        $controller = new ApiController('0.1.0', null, $detector);
        $payload = json_decode($controller->projectJson(), true);

        $this->assertTrue($payload['hasRectorAnalysis']);
    }

    public function test_analysis_payload_contains_expected_fields(): void
    {
        $analysisResult = new RectorAnalysisResult(
            true,
            'success',
            'vendor/bin/rector process --dry-run --output-format=json',
            0,
            3,
            '{"totals":{"changed_files":3}}',
            ''
        );

        $service = $this->createMock(RectorAnalysisService::class);
        $service->method('runDryRun')->willReturn($analysisResult);

        $controller = new ApiController('0.1.0', null, null, $service);
        $payload = json_decode($controller->analysisJson(), true);

        $this->assertTrue($payload['available']);
        $this->assertSame('success', $payload['status']);
        $this->assertSame(3, $payload['changedFilesCount']);
        $this->assertSame(0, $payload['exitCode']);
    }
}
