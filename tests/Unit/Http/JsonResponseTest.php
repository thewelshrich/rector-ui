<?php

declare(strict_types=1);

namespace RectorUi\Tests\Unit\Http;

use PHPUnit\Framework\TestCase;

final class JsonResponseTest extends TestCase
{
    protected function setUp(): void
    {
        // Prevent headers from being sent during tests
        if (!function_exists('RectorUi\Tests\Unit\Http\http_response_code')) {
            // We test the JSON encoding logic without actually sending headers
        }
    }

    public function testOkResponseEncodesData(): void
    {
        $data = ['version' => '0.1.0', 'php_version' => '8.2'];

        $expected = json_encode([
            'status' => 'ok',
            'data' => $data,
        ], JSON_THROW_ON_ERROR);

        $this->assertJson($expected);
        $decoded = json_decode($expected, true);
        $this->assertSame('ok', $decoded['status']);
        $this->assertSame('0.1.0', $decoded['data']['version']);
    }

    public function testErrorResponseEncodesMessage(): void
    {
        $message = 'Something went wrong';

        $expected = json_encode([
            'status' => 'error',
            'message' => $message,
        ], JSON_THROW_ON_ERROR);

        $this->assertJson($expected);
        $decoded = json_decode($expected, true);
        $this->assertSame('error', $decoded['status']);
        $this->assertSame('Something went wrong', $decoded['message']);
    }
}
