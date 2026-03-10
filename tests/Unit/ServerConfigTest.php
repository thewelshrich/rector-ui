<?php

namespace RectorUi\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RectorUi\ServerConfig;

class ServerConfigTest extends TestCase
{
    public function test_it_uses_defaults_when_no_arguments_are_provided(): void
    {
        $config = ServerConfig::fromArgv(array('bin/rector-ui'));

        $this->assertSame('127.0.0.1', $config->getHost());
        $this->assertSame(8080, $config->getPort());
        $this->assertTrue($config->shouldOpenBrowser());
    }

    public function test_it_parses_host_port_and_no_open_options(): void
    {
        $config = ServerConfig::fromArgv(array(
            'bin/rector-ui',
            '--host=0.0.0.0',
            '--port=9090',
            '--no-open',
        ));

        $this->assertSame('0.0.0.0', $config->getHost());
        $this->assertSame(9090, $config->getPort());
        $this->assertFalse($config->shouldOpenBrowser());
    }

    public function test_it_reads_dev_mode_from_environment(): void
    {
        putenv('RECTOR_UI_DEV=1');
        putenv('RECTOR_UI_DEV_SERVER=http://127.0.0.1:5173');

        $config = ServerConfig::fromArgv(array('bin/rector-ui'));

        $this->assertTrue($config->isDevMode());
        $this->assertSame('http://127.0.0.1:5173', $config->getDevServerUrl());

        putenv('RECTOR_UI_DEV');
        putenv('RECTOR_UI_DEV_SERVER');
    }
}
