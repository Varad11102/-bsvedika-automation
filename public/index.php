<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if ($path === '/health' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    http_response_code(200);
    echo json_encode([
        'status' => 'ok',
        'environment' => getenv('APP_ENV') ?: 'unconfigured',
        'database' => 'not-connected',
    ]);
    exit;
}

http_response_code(404);
echo json_encode([
    'error' => 'Not found',
    'message' => 'Production submission is intentionally disabled.',
]);
