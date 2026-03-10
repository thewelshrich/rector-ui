<?php

namespace RectorUi;

use RingCentral\Psr7\Response;

final class ApiController
{
    /** @var string */
    private $appVersion;

    /** @var RectorIntegrationStatusProvider */
    private $statusProvider;

    /** @var ProjectContextDetector */
    private $projectContextDetector;

    /** @var RectorAnalysisService */
    private $rectorAnalysisService;

    public function __construct(
        string $appVersion,
        ?RectorIntegrationStatusProvider $statusProvider = null,
        ?ProjectContextDetector $projectContextDetector = null,
        ?RectorAnalysisService $rectorAnalysisService = null
    )
    {
        $this->appVersion = $appVersion;
        $this->statusProvider = $statusProvider ?: new RectorIntegrationStatusProvider();
        $this->projectContextDetector = $projectContextDetector ?: new ProjectContextDetector();
        $this->rectorAnalysisService = $rectorAnalysisService ?: new RectorAnalysisService();
    }

    public function healthJson(): string
    {
        return json_encode(array(
            'status' => 'ok',
            'phpVersion' => PHP_VERSION,
            'appVersion' => $this->appVersion,
        ));
    }

    public function metaJson(): string
    {
        return json_encode(array(
            'appName' => 'Rector UI',
            'version' => $this->appVersion,
            'runtime' => array(
                'php' => PHP_VERSION,
                'os' => PHP_OS_FAMILY,
            ),
            'capabilities' => array(
                'rector' => $this->statusProvider->isAvailable(),
            ),
        ));
    }

    public function healthResponse(): Response
    {
        return $this->jsonResponse($this->healthJson());
    }

    public function metaResponse(): Response
    {
        return $this->jsonResponse($this->metaJson());
    }

    public function projectJson(): string
    {
        $payload = $this->projectContextDetector->detect()->toArray();
        $payload['status'] = 'ok';

        return json_encode($payload);
    }

    public function projectResponse(): Response
    {
        return $this->jsonResponse($this->projectJson());
    }

    public function analysisJson(): string
    {
        return json_encode($this->rectorAnalysisService->runDryRun()->toArray());
    }

    public function analysisResponse(): Response
    {
        return $this->jsonResponse($this->analysisJson());
    }

    private function jsonResponse(string $json): Response
    {
        return new Response(
            200,
            array(
                'Content-Type' => 'application/json',
            ),
            $json
        );
    }
}
