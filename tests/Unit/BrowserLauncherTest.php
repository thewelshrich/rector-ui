<?php

namespace RectorUi\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RectorUi\BrowserLauncher;

class BrowserLauncherTest extends TestCase
{
    public function test_it_builds_macos_open_command(): void
    {
        $launcher = new BrowserLauncher();

        $command = $launcher->buildCommand('Darwin', 'http://127.0.0.1:8080');

        $this->assertSame('open \'http://127.0.0.1:8080\'', $command);
    }

    public function test_it_builds_linux_open_command(): void
    {
        $launcher = new BrowserLauncher();

        $command = $launcher->buildCommand('Linux', 'http://127.0.0.1:8080');

        $this->assertSame('xdg-open \'http://127.0.0.1:8080\' > /dev/null 2>&1 &', $command);
    }

    public function test_it_returns_null_for_unsupported_os(): void
    {
        $launcher = new BrowserLauncher();

        $this->assertNull($launcher->buildCommand('BSD', 'http://127.0.0.1:8080'));
    }
}
