<?php

declare(strict_types=1);

namespace RectorUi\Tests\Unit\Console;

use PHPUnit\Framework\TestCase;
use RectorUi\Console\ServeCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

final class ServeCommandTest extends TestCase
{
    private CommandTester $tester;

    protected function setUp(): void
    {
        $application = new Application();
        $application->add(new ServeCommand());

        $command = $application->find('serve');
        $this->tester = new CommandTester($command);
    }

    public function testItHasCorrectDefaultOptions(): void
    {
        $definition = $this->tester->getCommand()->getDefinition();

        $this->assertTrue($definition->hasOption('host'));
        $this->assertTrue($definition->hasOption('port'));
        $this->assertTrue($definition->hasOption('no-open'));

        $hostOption = $definition->getOption('host');
        $this->assertSame('localhost', $hostOption->getDefault());

        $portOption = $definition->getOption('port');
        $this->assertSame(8199, $portOption->getDefault());
    }

    public function testItFailsWhenRouterScriptIsMissing(): void
    {
        // Force a non-existent router path by pointing to a temp dir
        $application = new Application();
        $command = new ServeCommand();

        // We can't easily mock the router path, so we test the command
        // with a temporary directory that lacks router.php
        $originalDir = getcwd();
        $tempDir = sys_get_temp_dir() . '/rector_ui_test_' . uniqid();
        mkdir($tempDir, 0777, true);
        chdir($tempDir);

        try {
            $application->add($command);
            $tester = new CommandTester($application->find('serve'));

            // Point to the temp dir's non-existent router
            $exitCode = $tester->execute([]);

            $this->assertSame(1, $exitCode);
            $this->assertStringContainsString('Router script not found', $tester->getDisplay());
        } finally {
            chdir($originalDir);
            rmdir($tempDir);
        }
    }
}
