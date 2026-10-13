<?php
/**
 * Practical 13 – PHP JSON API & AJAX/Fetch CRUD
 * includes/response.php  –  Centralised JSON response helpers
 *
 * Advanced Extension: centralised status codes + error handling
 */

/**
 * Send a JSON response and exit.
 *
 * @param bool   $success
 * @param string $message  Human-readable message
 * @param array  $data     Optional payload
 * @param int    $code     HTTP status code
 */
function json_respond(bool $success, string $message, array $data = [], int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');

    echo json_encode([
        'success'   => $success,
        'message'   => $message,
        'data'      => $data,
        'timestamp' => date('c'),   // ISO 8601
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function api_ok(string $message = 'OK', array $data = [], int $code = 200): never
{
    json_respond(true, $message, $data, $code);
}

function api_error(string $message = 'Error', int $code = 400, array $data = []): never
{
    json_respond(false, $message, $data, $code);
}

/** Read + decode JSON request body (for PUT/PATCH/DELETE with body). */
function json_body(): array
{
    $raw = file_get_contents('php://input');
    return $raw ? (json_decode($raw, true) ?? []) : [];
}
