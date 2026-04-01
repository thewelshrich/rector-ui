<?php

declare(strict_types=1);

namespace RectorUi\Http;

/**
 * Lightweight JSON response helper.
 * Implementation will be expanded in Phase 1.
 */
final class JsonResponse
{
    public static function ok(mixed $data = null, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'ok',
            'data' => $data,
        ], JSON_THROW_ON_ERROR);
    }

    public static function error(string $message, int $status = 400): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => $message,
        ], JSON_THROW_ON_ERROR);
    }
}
