<?php

/**
 * Router script for PHP's built-in development server.
 *
 * Usage: php -S localhost:8199 bin/router.php
 */

declare(strict_types=1);

$publicPath = __DIR__ . '/../public';

// Serve static files from public/
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestedFile = $publicPath . $uri;

if ($uri !== '/' && file_exists($requestedFile) && !is_dir($requestedFile)) {
    return false; // Let PHP's built-in server handle the static file
}

// Serve index.html for the SPA fallback
if ($uri === '/' || !file_exists($requestedFile)) {
    $indexPath = $publicPath . '/index.html';

    if (file_exists($indexPath)) {
        return false; // Serve index.html
    }
}

// API routes will be handled here in Phase 1
if (str_starts_with($uri, '/api/')) {
    http_response_code(503);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => 'API not yet implemented',
        'status' => 'service_unavailable',
    ]);

    return true;
}

// 404 fallback
http_response_code(404);
header('Content-Type: text/plain');
echo '404 Not Found';

return true;
