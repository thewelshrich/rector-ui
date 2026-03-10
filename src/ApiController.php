<?php

namespace RectorUi;

use RingCentral\Psr7\Response;

final class ApiController
{
    /** @var string */
    private $appVersion;

    /** @var RectorIntegrationStatusProvider */
    private $statusProvider;

    public function __construct(string $appVersion, ?RectorIntegrationStatusProvider $statusProvider = null)
    {
        $this->appVersion = $appVersion;
        $this->statusProvider = $statusProvider ?: new RectorIntegrationStatusProvider();
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
