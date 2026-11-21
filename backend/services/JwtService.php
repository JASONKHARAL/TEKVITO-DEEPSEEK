<?php
// backend/services/JwtService.php

declare(strict_types=1);

final class JwtService
{
    public static function sign(array $payload, ?int $ttl = null): string
    {
        $secret = env('JWT_SECRET');
        if (!$secret) {
            throw new RuntimeException('JWT_SECRET is not set in .env');
        }

        $ttl = $ttl ?? (int) env('JWT_TTL_SECONDS', '86400');

        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $payload['iat'] = time();
        $payload['exp'] = time() + $ttl;

        $h = self::b64(json_encode($header));
        $p = self::b64(json_encode($payload));
        $sig = self::b64(hash_hmac('sha256', "$h.$p", $secret, true));

        return "$h.$p.$sig";
    }

    public static function verify(string $token): ?array
    {
        $secret = env('JWT_SECRET');
        if (!$secret) return null;

        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;
        [$h, $p, $sig] = $parts;

        $expected = self::b64(hash_hmac('sha256', "$h.$p", $secret, true));
        if (!hash_equals($expected, $sig)) return null;

        $payload = json_decode(self::unb64($p), true);
        if (!is_array($payload)) return null;
        if (isset($payload['exp']) && time() >= (int) $payload['exp']) return null;

        return $payload;
    }

    private static function b64(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    private static function unb64(string $b64): string
    {
        $pad = strlen($b64) % 4;
        if ($pad) $b64 .= str_repeat('=', 4 - $pad);
        return base64_decode(strtr($b64, '-_', '+/')) ?: '';
    }
}