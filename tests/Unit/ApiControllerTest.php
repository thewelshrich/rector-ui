<?php

namespace RectorUi\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RectorUi\ApiController;

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
}
