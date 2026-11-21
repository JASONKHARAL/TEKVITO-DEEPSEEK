<?php
// backend/middleware/AuthMiddleware.php

declare(strict_types=1);

function require_auth(): array
{
    $header = $_SERVER['HTTP_AUTHORIZATION']
        ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        ?? '';

    if (!preg_match('/Bearer\s+(\S+)/', $header, $m)) {
        json_out(['ok' => false, 'error' => 'Missing bearer token'], 401);
    }

    $payload = JwtService::verify($m[1]);
    if (!$payload) {
        json_out(['ok' => false, 'error' => 'Invalid or expired token'], 401);
    }

    return $payload;
}