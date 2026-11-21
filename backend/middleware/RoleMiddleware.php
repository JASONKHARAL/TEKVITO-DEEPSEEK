<?php
// backend/middleware/RoleMiddleware.php

declare(strict_types=1);

function require_role(string ...$roles): array
{
    $payload = require_auth();
    $userRoles = $payload['roles'] ?? [];
    foreach ($roles as $r) {
        if (in_array($r, $userRoles, true)) {
            return $payload;
        }
    }
    json_out(['ok' => false, 'error' => 'Insufficient role'], 403);
}