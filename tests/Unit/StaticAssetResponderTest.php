<?php

namespace RectorUi\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use RectorUi\ApiController;
use RectorUi\HttpApplication;
use RectorUi\RectorIntegrationStatusProvider;
use RectorUi\StaticAssetResponder;
use RingCentral\Psr7\ServerRequest;

class StaticAssetResponderTest extends TestCase
{
    public function test_it_serves_index_for_unknown_client_routes(): void
    {
        $app = $this->createApplication();

        $response = $app(new ServerRequest('GET', 'http://localhost/dashboard'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('<div id="root"></div>', (string) $response->getBody());
    }

    public function test_it_returns_404_for_missing_assets(): void
    {
        $app = $this->createApplication();

        $response = $app(new ServerRequest('GET', 'http://localhost/assets/missing.js'));

        $this->assertSame(404, $response->getStatusCode());
    }

    public function test_it_routes_health_requests_to_api_controller(): void
    {
        $app = $this->createApplication();

        $response = $app(new ServerRequest('GET', 'http://localhost/api/health'));
        $payload = json_decode((string) $response->getBody(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('ok', $payload['status']);
    }

    public function test_it_routes_project_requests_to_api_controller(): void
    {
        $app = $this->createApplication();

        $response = $app(new ServerRequest('GET', 'http://localhost/api/project'));
        $payload = json_decode((string) $response->getBody(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertArrayHasKey('path', $payload);
        $this->assertArrayHasKey('hasComposerJson', $payload);
    }

    public function test_it_routes_analysis_posts_to_api_controller(): void
    {
        $app = $this->createApplication();

        $response = $app(new ServerRequest('POST', 'http://localhost/api/analysis'));
        $payload = json_decode((string) $response->getBody(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertArrayHasKey('available', $payload);
        $this->assertArrayHasKey('status', $payload);
    }

    public function test_it_serves_vite_html_shell_in_dev_mode(): void
    {
        $app = $this->createApplication(true);

        $response = $app(new ServerRequest('GET', 'http://localhost/review'));
        $body = (string) $response->getBody();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('http://127.0.0.1:5173/@vite/client', $body);
        $this->assertStringContainsString('http://127.0.0.1:5173/src/main.jsx', $body);
    }

    /**
     * @return callable(ServerRequestInterface): \Psr\Http\Message\ResponseInterface
     */
    private function createApplication(bool $devMode = false): callable
    {
        $publicPath = __DIR__ . '/../../public';
        $responder = new StaticAssetResponder($publicPath, $devMode, 'http://127.0.0.1:5173');
        $controller = new ApiController('0.1.0', new RectorIntegrationStatusProvider());

        return new HttpApplication($responder, $controller);
    }
}
