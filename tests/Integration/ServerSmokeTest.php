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

    public function test_cli_server_reports_project_context_for_target_directory(): void
    {
        $port = 8101;
        $projectDirectory = sys_get_temp_dir() . '/rector-ui-integration-' . uniqid('', true);
        mkdir($projectDirectory, 0777, true);
        $projectDirectory = realpath($projectDirectory) ?: $projectDirectory;
        file_put_contents($projectDirectory . '/composer.json', "{}\n");
        file_put_contents($projectDirectory . '/rector.php.dist', "<?php\n");
        mkdir($projectDirectory . '/vendor', 0777, true);
        mkdir($projectDirectory . '/vendor/bin', 0777, true);
        file_put_contents($projectDirectory . '/vendor/bin/rector', "#!/usr/bin/env php\n");

        $this->process = $this->startServerProcess($port, array(), $projectDirectory);
        $this->assertIsResource($this->process);
        $this->waitForServer($port);

        $project = json_decode((string) file_get_contents('http://127.0.0.1:' . $port . '/api/project'), true);

        $this->assertSame('ok', $project['status']);
        $this->assertSame($projectDirectory, $project['path']);
        $this->assertTrue($project['hasComposerJson']);
        $this->assertTrue($project['hasRectorBinary']);
        $this->assertTrue($project['hasRectorConfig']);
        $this->assertTrue($project['hasRectorAnalysis']);
        $this->assertSame($projectDirectory . '/rector.php.dist', $project['rectorConfigPath']);
    }

    public function test_cli_server_reports_unavailable_analysis_without_rector_setup(): void
    {
        $port = 8102;
        $projectDirectory = sys_get_temp_dir() . '/rector-ui-analysis-missing-' . uniqid('', true);
        mkdir($projectDirectory, 0777, true);
        $projectDirectory = realpath($projectDirectory) ?: $projectDirectory;
        file_put_contents($projectDirectory . '/composer.json', "{}\n");

        $this->process = $this->startServerProcess($port, array(), $projectDirectory);
        $this->assertIsResource($this->process);
        $this->waitForServer($port);

        $analysis = json_decode($this->postJson('http://127.0.0.1:' . $port . '/api/analysis'), true);

        $this->assertFalse($analysis['available']);
        $this->assertSame('unavailable', $analysis['status']);
    }

    public function test_cli_server_runs_stubbed_rector_dry_run(): void
    {
        $port = 8103;
        $projectDirectory = sys_get_temp_dir() . '/rector-ui-analysis-ready-' . uniqid('', true);
        mkdir($projectDirectory, 0777, true);
        $projectDirectory = realpath($projectDirectory) ?: $projectDirectory;
        file_put_contents($projectDirectory . '/composer.json', "{}\n");
        file_put_contents($projectDirectory . '/rector.php', "<?php\n");
        mkdir($projectDirectory . '/vendor', 0777, true);
        mkdir($projectDirectory . '/vendor/bin', 0777, true);
        file_put_contents(
            $projectDirectory . '/vendor/bin/rector',
            "#!/usr/bin/env php\n<?php\necho json_encode(['totals' => ['changed_files' => 2]]);\n"
        );
        chmod($projectDirectory . '/vendor/bin/rector', 0755);

        $this->process = $this->startServerProcess($port, array(), $projectDirectory);
        $this->assertIsResource($this->process);
        $this->waitForServer($port);

        $analysis = json_decode($this->postJson('http://127.0.0.1:' . $port . '/api/analysis'), true);

        $this->assertTrue($analysis['available']);
        $this->assertSame('success', $analysis['status']);
        $this->assertSame(2, $analysis['changedFilesCount']);
    }

    /**
     * @param array<string, string> $environment
     * @return resource
     */
    private function startServerProcess(int $port, array $environment = array(), ?string $workingDirectory = null)
    {
        $command = sprintf('php %s/bin/rector-ui --no-open --port=%d', dirname(__DIR__, 2), $port);
        $processEnvironment = $environment === array() ? null : array_merge($_ENV, $environment);

        return proc_open(
            $command,
            array(
                0 => array('pipe', 'r'),
                1 => array('pipe', 'w'),
                2 => array('pipe', 'w'),
            ),
            $this->pipes,
            $workingDirectory ?: dirname(__DIR__, 2),
            $processEnvironment
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

    private function postJson(string $url): string
    {
        $context = stream_context_create(array(
            'http' => array(
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => '',
            ),
        ));

        return (string) file_get_contents($url, false, $context);
    }
}
