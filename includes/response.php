<?php
/**
 * JSON Response & API Utility
 */

function json_response(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function json_error(string $message, int $statusCode = 400, array $extra = []): void {
    json_response(array_merge([
        'success' => false,
        'error' => $message
    ], $extra), $statusCode);
}

function json_success(array $data = [], string $message = ''): void {
    $payload = ['success' => true];
    if ($message !== '') {
        $payload['message'] = $message;
    }
    json_response(array_merge($payload, $data), 200);
}
