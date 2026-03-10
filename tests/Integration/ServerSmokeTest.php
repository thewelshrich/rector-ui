<?php

namespace RectorUi\Tests\Integration;

use PHPUnit\Framework\TestCase;

class ServerSmokeTest extends TestCase
{
    /** @var resource|null */
    private $process;

    /** @var array<int, resource> */
    private $pipes = array();

    protected function tearDown(): void
    {
        if (is_resource($this->process)) {
            proc_terminate($this->process);
            proc_close($this->process);
        }
    }

    public function test_cli_server_serves_health_and_client_routes(): void
    {
        $port = 8099;
        $this->process = $this->startServerProcess($port);

        $this->assertIsResource($this->process);
        $this->waitForServer($port);

        $health = json_decode((string) file_get_contents('http://127.0.0.1:' . $port . '/api/health'), true);
        $html = (string) file_get_contents('http://127.0.0.1:' . $port . '/review');
        preg_match('/src="([^"]+assets\/[^"]+\.js)"/', $html, $matches);

        $asset = isset($matches[1]) ? (string) file_get_contents('http://127.0.0.1:' . $port . $matches[1]) : '';

        $this->assertSame('ok', $health['status']);
        $this->assertStringContainsString('<div id="root"></div>', $html);
        $this->assertStringContainsString('createRoot', $asset);
    }

    public function test_cli_server_serves_vite_shell_in_dev_mode(): void
    {
        $port = 8100;
        $this->process = $this->startServerProcess(
            $port,
            array(
                'RECTOR_UI_DEV' => '1',
                'RECTOR_UI_DEV_SERVER' => 'http://127.0.0.1:5173',
            )
        );

        $this->assertIsResource($this->process);
        $this->waitForServer($port);

        $html = (string) file_get_contents('http://127.0.0.1:' . $port . '/review');
        $health = json_decode((string) file_get_contents('http://127.0.0.1:' . $port . '/api/health'), true);

        $this->assertStringContainsString('http://127.0.0.1:5173/@vite/client', $html);
        $this->assertStringContainsString('http://127.0.0.1:5173/src/main.jsx', $html);
        $this->assertSame('ok', $health['status']);
    }

    /**
     * @param array<string, string> $environment
     * @return resource
     */
    private function startServerProcess(int $port, array $environment = array())
    {
        $command = sprintf('php %s/bin/rector-ui --no-open --port=%d', dirname(__DIR__, 2), $port);

        return proc_open(
            $command,
            array(
                0 => array('pipe', 'r'),
                1 => array('pipe', 'w'),
                2 => array('pipe', 'w'),
            ),
            $this->pipes,
            dirname(__DIR__, 2),
            $environment
        );
    }

    private function waitForServer(int $port): void
    {
        $attempts = 20;

        while ($attempts > 0) {
            $connection = @fsockopen('127.0.0.1', $port, $errorCode, $errorMessage, 0.2);

            if (is_resource($connection)) {
                fclose($connection);
                return;
            }

            usleep(100000);
            $attempts--;
        }

        $stdout = isset($this->pipes[1]) ? stream_get_contents($this->pipes[1]) : '';
        $stderr = isset($this->pipes[2]) ? stream_get_contents($this->pipes[2]) : '';

        $this->fail('Server did not start in time. STDOUT: ' . $stdout . ' STDERR: ' . $stderr);
    }
}
