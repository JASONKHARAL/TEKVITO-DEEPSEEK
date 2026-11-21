<?php
// backend/middleware/PermissionMiddleware.php

declare(strict_types=1);

function require_permission(string $permission): array
{
    $payload = require_auth();

    $userId = (int) ($payload['sub'] ?? 0);
    if (!$userId) {
        json_out(['ok' => false, 'error' => 'Invalid token subject'], 401);
    }

    $perms = User::getPermissions($userId);
    if (!in_array($permission, $perms, true)) {
        json_out(['ok' => false, 'error' => "Missing permission: $permission"], 403);
    }

    return $payload;
}