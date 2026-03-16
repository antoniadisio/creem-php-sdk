<?php

declare(strict_types=1);

use Playground\Support\Playground;
use Playground\Support\WebhookPlayground;

$bootstrap = require dirname(__DIR__) . '/bootstrap.php';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if ($method === 'GET' && $path === '/health') {
    header('Content-Type: application/json');
    Playground::printJson(WebhookPlayground::captureHealth($bootstrap['workspace_path']));

    return;
}

if ($method !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    Playground::printJson([
        'ok' => false,
        'error' => 'Method not allowed.',
        'method' => $method,
        'path' => $path,
    ]);

    return;
}

$headers = function_exists('getallheaders') ? getallheaders() : [];
$payload = file_get_contents('php://input');

if (! is_string($payload) || $payload === '') {
    $payloadFile = getenv('CREEM_PLAYGROUND_WEBHOOK_INPUT_FILE');

    if (is_string($payloadFile) && $payloadFile !== '') {
        $overridePayload = file_get_contents($payloadFile);

        if (is_string($overridePayload)) {
            $payload = $overridePayload;
        }
    }
}

$payload ??= '';

header('Content-Type: application/json');
Playground::printJson(WebhookPlayground::capture(
    $bootstrap['workspace_path'],
    $path,
    is_array($headers) ? $headers : [],
    $payload,
));
