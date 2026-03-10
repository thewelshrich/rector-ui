<?php

namespace RectorUi;

use RingCentral\Psr7\Response;

final class StaticAssetResponder
{
    /** @var string */
    private $publicPath;

    /** @var bool */
    private $devMode;

    /** @var string */
    private $devServerUrl;

    public function __construct(string $publicPath, bool $devMode = false, string $devServerUrl = 'http://127.0.0.1:5173')
    {
        $this->publicPath = rtrim($publicPath, '/');
        $this->devMode = $devMode;
        $this->devServerUrl = rtrim($devServerUrl, '/');
    }

    public function hasBuiltAssets(): bool
    {
        if ($this->devMode) {
            return true;
        }

        return file_exists($this->publicPath . '/index.html');
    }

    public function isStaticAssetRequest(string $path): bool
    {
        if (strpos($path, '/assets/') === 0) {
            return true;
        }

        return (bool) preg_match('/\.[a-z0-9]+$/i', $path);
    }

    public function assetResponse(string $path): Response
    {
        $filePath = $this->resolvePath($path);

        if ($filePath === null || ! file_exists($filePath) || ! is_file($filePath)) {
            return new Response(404, array('Content-Type' => 'text/plain'), 'Not Found');
        }

        return new Response(
            200,
            array('Content-Type' => $this->detectContentType($filePath)),
            (string) file_get_contents($filePath)
        );
    }

    public function indexResponse(): Response
    {
        if ($this->devMode) {
            return new Response(
                200,
                array('Content-Type' => 'text/html; charset=UTF-8'),
                $this->buildDevIndexHtml()
            );
        }

        return $this->assetResponse('/index.html');
    }

    private function buildDevIndexHtml(): string
    {
        return sprintf(
            '<!doctype html><html lang="en"><head><meta charset="UTF-8" />'
            . '<meta name="viewport" content="width=device-width, initial-scale=1.0" />'
            . '<title>Rector UI</title>'
            . '<script type="module" src="%s/@vite/client"></script>'
            . '<script type="module" src="%s/src/main.jsx"></script>'
            . '</head><body><div id="root"></div></body></html>',
            htmlspecialchars($this->devServerUrl, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($this->devServerUrl, ENT_QUOTES, 'UTF-8')
        );
    }

    private function resolvePath(string $path): ?string
    {
        $cleanPath = ltrim($path, '/');
        $cleanPath = str_replace(array('../', '..\\'), '', $cleanPath);

        if ($cleanPath === '') {
            $cleanPath = 'index.html';
        }

        return $this->publicPath . '/' . $cleanPath;
    }

    private function detectContentType(string $filePath): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if ($extension === 'html') {
            return 'text/html; charset=UTF-8';
        }

        if ($extension === 'css') {
            return 'text/css; charset=UTF-8';
        }

        if ($extension === 'js') {
            return 'application/javascript; charset=UTF-8';
        }

        if ($extension === 'json') {
            return 'application/json';
        }

        if ($extension === 'svg') {
            return 'image/svg+xml';
        }

        return 'text/plain; charset=UTF-8';
    }
}
