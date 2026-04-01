<?php

declare(strict_types=1);

namespace RectorUi\Tests\Unit\Application;

use PHPUnit\Framework\TestCase;
use RectorUi\Application\Container;

final class ContainerTest extends TestCase
{
    public function testContainerStoresAndRetrievesServices(): void
    {
        $container = new Container();
        $service = new \stdClass();
        $service->name = 'test';

        $container->set('test_service', $service);

        $this->assertTrue($container->has('test_service'));
        $this->assertSame($service, $container->get('test_service'));
    }

    public function testContainerThrowsOnMissingService(): void
    {
        $container = new Container();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Service "missing" not found in container.');

        $container->get('missing');
    }

    public function testContainerReportsMissingService(): void
    {
        $container = new Container();

        $this->assertFalse($container->has('nonexistent'));
    }
}
