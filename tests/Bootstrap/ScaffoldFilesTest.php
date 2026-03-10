<?php

namespace RectorUi\Tests\Bootstrap;

use PHPUnit\Framework\TestCase;

class ScaffoldFilesTest extends TestCase
{
    public function test_bin_entrypoint_exists(): void
    {
        $this->assertFileExists(__DIR__ . '/../../bin/rector-ui');
    }

    public function test_public_frontend_entrypoint_exists(): void
    {
        $this->assertFileExists(__DIR__ . '/../../public/index.html');
    }
}
