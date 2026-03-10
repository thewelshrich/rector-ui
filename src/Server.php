<?php

namespace RectorUi;

use React\EventLoop\Loop;
use React\Http\HttpServer;
use React\Socket\SocketServer;
use Throwable;

final class Server
{
    /** @var ServerConfig */
    private $config;

    /** @var HttpApplication */
    private $application;

    /** @var BrowserLauncher */
    private $browserLauncher;

    public function __construct(ServerConfig $config, HttpApplication $application, BrowserLauncher $browserLauncher)
    {
        $this->config = $config;
        $this->application = $application;
        $this->browserLauncher = $browserLauncher;
    }

    public function run(): void
    {
        if (! $this->hasBuiltFrontend()) {
            throw new \RuntimeException('Frontend build is missing. Build or package the assets into public/.');
        }

        $loop = Loop::get();
        $server = new HttpServer($loop, $this->application);

        try {
            $socket = new SocketServer(
                sprintf('%s:%d', $this->config->getHost(), $this->config->getPort()),
                array(),
                $loop
            );
        } catch (Throwable $throwable) {
            throw new \RuntimeException(
                sprintf(
                    'Unable to start Rector UI on %s. %s',
                    $this->config->getUrl(),
                    $throwable->getMessage()
                )
            );
        }

        $server->listen($socket);

        fwrite(STDOUT, "Rector UI starting...\n");
        fwrite(STDOUT, sprintf("Open %s in your browser\n", $this->config->getUrl()));
        if ($this->config->isDevMode()) {
            fwrite(STDOUT, sprintf("Dev mode enabled via %s\n", $this->config->getDevServerUrl()));
        }
        fwrite(STDOUT, "Press Ctrl+C to stop\n");

        if ($this->config->shouldOpenBrowser()) {
            $this->browserLauncher->open(PHP_OS_FAMILY, $this->config->getUrl());
        }

        $loop->run();
    }

    private function hasBuiltFrontend(): bool
    {
        $responder = new StaticAssetResponder(
            dirname(__DIR__) . '/public',
            $this->config->isDevMode(),
            $this->config->getDevServerUrl()
        );

        return $responder->hasBuiltAssets();
    }
}
