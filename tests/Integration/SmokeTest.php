<?php

declare(strict_types=1);

namespace RectorUi\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Integration tests will be added in Phase 1.
 * These tests verify that services work together correctly.
 */
final class SmokeTest extends TestCase
{
    public function testApplicationBoots(): void
    {
        // Verify autoloading works for the main namespace
        $this->assertTrue(
            class_exists(\RectorUi\Application\Kernel::class),
            'Kernel class should be autoloadable',
        );
        $this->assertTrue(
            class_exists(\RectorUi\Console\ServeCommand::class),
            'ServeCommand class should be autoloadable',
        );
    }
}
