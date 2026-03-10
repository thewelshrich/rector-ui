<?php

namespace RectorUi;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RingCentral\Psr7\Response;

final class HttpApplication
{
    /** @var StaticAssetResponder */
    private $staticAssetResponder;

    /** @var ApiController */
    private $apiController;

    public function __construct(StaticAssetResponder $staticAssetResponder, ApiController $apiController)
    {
        $this->staticAssetResponder = $staticAssetResponder;
        $this->apiController = $apiController;
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $path = $request->getUri()->getPath();

        if ($path === '/api/health') {
            return $this->apiController->healthResponse();
        }

        if ($path === '/api/meta') {
            return $this->apiController->metaResponse();
        }

        if ($path === '/api/project') {
            return $this->apiController->projectResponse();
        }

        if ($path === '/api/analysis' && $request->getMethod() === 'POST') {
            return $this->apiController->analysisResponse();
        }

        if (strpos($path, '/api/') === 0) {
            return new Response(404, array('Content-Type' => 'application/json'), '{"error":"Not Found"}');
        }

        if ($this->staticAssetResponder->isStaticAssetRequest($path)) {
            return $this->staticAssetResponder->assetResponse($path);
        }

        return $this->staticAssetResponder->indexResponse();
    }
}
