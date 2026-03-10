<?php

namespace RectorUi;

final class ServerConfig
{
    /** @var string */
    private $host;

    /** @var int */
    private $port;

    /** @var bool */
    private $openBrowser;

    /** @var bool */
    private $helpRequested;

    /** @var bool */
    private $devMode;

    /** @var string */
    private $devServerUrl;

    private function __construct(
        string $host,
        int $port,
        bool $openBrowser,
        bool $helpRequested,
        bool $devMode,
        string $devServerUrl
    )
    {
        $this->host = $host;
        $this->port = $port;
        $this->openBrowser = $openBrowser;
        $this->helpRequested = $helpRequested;
        $this->devMode = $devMode;
        $this->devServerUrl = $devServerUrl;
    }

    /**
     * @param array<int, string> $argv
     */
    public static function fromArgv(array $argv): self
    {
        $host = '127.0.0.1';
        $port = 8080;
        $openBrowser = true;
        $helpRequested = false;
        $devMode = false;
        $devServerUrl = 'http://127.0.0.1:5173';

        $devEnvValue = getenv('RECTOR_UI_DEV');
        $devServerEnvValue = getenv('RECTOR_UI_DEV_SERVER');

        if ($devEnvValue !== false && $devEnvValue !== '' && $devEnvValue !== '0') {
            $devMode = true;
        }

        if ($devServerEnvValue !== false && $devServerEnvValue !== '') {
            $devServerUrl = rtrim((string) $devServerEnvValue, '/');
        }

        foreach ($argv as $index => $argument) {
            if ($index === 0) {
                continue;
            }

            if ($argument === '--no-open') {
                $openBrowser = false;
                continue;
            }

            if ($argument === '--help' || $argument === '-h') {
                $helpRequested = true;
                continue;
            }

            if (strpos($argument, '--host=') === 0) {
                $host = (string) substr($argument, 7);
                continue;
            }

            if (strpos($argument, '--port=') === 0) {
                $port = (int) substr($argument, 7);
                continue;
            }
        }

        return new self($host, $port, $openBrowser, $helpRequested, $devMode, $devServerUrl);
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function shouldOpenBrowser(): bool
    {
        return $this->openBrowser;
    }

    public function isHelpRequested(): bool
    {
        return $this->helpRequested;
    }

    public function isDevMode(): bool
    {
        return $this->devMode;
    }

    public function getDevServerUrl(): string
    {
        return $this->devServerUrl;
    }

    public function getUrl(): string
    {
        return sprintf('http://%s:%d', $this->host, $this->port);
    }
}
