<?php
// backend/controllers/AuthController.php

declare(strict_types=1);

final class AuthController
{
    public static function login(): void
    {
        $data = read_json();
        $email = trim((string) ($data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        if ($email === '' || $password === '') {
            json_out(['ok' => false, 'error' => 'Email and password are required'], 422);
        }

        $user = User::verifyPassword($email, $password);
        if (!$user) {
            json_out(['ok' => false, 'error' => 'Invalid credentials'], 401);
        }

        if (empty($user['email_verified_at'])) {
            json_out([
                'ok'    => false,
                'error' => 'Please verify your email before logging in',
                'code'  => 'EMAIL_NOT_VERIFIED',
                'email' => $email,
            ], 403);
        }

        $roles = User::getRoles((int) $user['id']);
        $token = JwtService::sign([
            'sub'   => $user['id'],
            'email' => $user['email'],
            'roles' => $roles,
        ]);

        json_out([
            'ok'    => true,
            'token' => $token,
            'user'  => [
                'id'         => (int) $user['id'],
                'email'      => $user['email'],
                'first_name' => $user['first_name'],
                'last_name'  => $user['last_name'],
                'roles'      => $roles,
            ],
        ]);
    }

    public static function register(): void
    {
        $data = read_json();
        $email     = trim((string) ($data['email'] ?? ''));
        $password  = (string) ($data['password'] ?? '');
        $firstName = trim((string) ($data['first_name'] ?? ''));
        $lastName  = trim((string) ($data['last_name'] ?? ''));

        if ($email === '' || $password === '' || $firstName === '' || $lastName === '') {
            json_out(['ok' => false, 'error' => 'All fields are required'], 422);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_out(['ok' => false, 'error' => 'Invalid email format'], 422);
        }
        if (strlen($password) < 8) {
            json_out(['ok' => false, 'error' => 'Password must be at least 8 characters'], 422);
        }
        // Verify Cloudflare Turnstile before creating an account.
$turnstileToken = trim((string) ($data['turnstile_token'] ?? ''));
$turnstileSecret = env('TURNSTILE_SECRET_KEY');

if ($turnstileToken === '' || !$turnstileSecret) {
    json_out([
        'ok' => false,
        'error' => 'Please complete the security verification.'
    ], 422);
}

$ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');

curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'secret' => $turnstileSecret,
        'response' => $turnstileToken,
    ]),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT => 10,
]);

$verificationResponse = curl_exec($ch);
$httpStatus = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);

curl_close($ch);

if ($verificationResponse === false || $httpStatus !== 200) {
    error_log('Turnstile verification request failed: ' . $curlError);

    json_out([
        'ok' => false,
        'error' => 'Security verification is temporarily unavailable. Please try again.'
    ], 503);
}

$verification = json_decode($verificationResponse, true);

if (!is_array($verification) || ($verification['success'] ?? false) !== true) {
    json_out([
        'ok' => false,
        'error' => 'Security verification failed. Please try again.'
    ], 422);
}
        if (User::findByEmail($email)) {
            json_out(['ok' => false, 'error' => 'Email already registered'], 409);
        }

        $userId = User::create($email, $password, $firstName, $lastName);

        $rawToken = EmailVerification::issue($userId);
        EmailService::sendVerification($userId, $email, $firstName, $rawToken);
        $roles = User::getRoles($userId);
        $token = JwtService::sign([
            'sub'   => $userId,
            'email' => $email,
            'roles' => $roles,
        ]);

        json_out([
            'ok' => true,
            'requires_verification' => true,
            'email' => $email,
            'message' => 'Check your email to verify your account before logging in.',
        ], 201);
    }
    
    public static function forgotPassword(): void
    {
        $data = read_json();
        $email = trim((string) ($data['email'] ?? ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_out([
                'ok' => false,
                'error' => 'Please enter a valid email address.'
            ], 422);
        }

        // Always return the same message to avoid revealing registered emails.
        $response = [
            'ok' => true,
            'message' => 'If an account exists for that email, a password reset link has been sent.'
        ];

        $user = User::findByEmail($email);

        if (!$user || ($user['account_status'] ?? '') !== 'active') {
            json_out($response);
        }

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $pdo = db();

        try {
            $pdo->beginTransaction();

            // Invalidate previous unused reset tokens.
            $stmt = $pdo->prepare(
                'UPDATE password_reset_tokens
                 SET used_at = NOW()
                 WHERE user_id = ? AND used_at IS NULL'
            );
            $stmt->execute([(int) $user['id']]);

            // Store only the SHA-256 hash of the token.
            $stmt = $pdo->prepare(
                'INSERT INTO password_reset_tokens
                 (user_id, token_hash, expires_at, requested_ip)
                 VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE), ?)'
            );

            $stmt->execute([
                (int) $user['id'],
                $tokenHash,
                $_SERVER['REMOTE_ADDR'] ?? null
            ]);

            $pdo->commit();

            $frontendUrl = rtrim(
                env('FRONTEND_URL', 'http://localhost:5173'),
                '/'
            );

            $resetUrl = $frontendUrl . '/reset-password?token=' .
                rawurlencode($token);

            $safeUrl = htmlspecialchars(
                $resetUrl,
                ENT_QUOTES,
                'UTF-8'
            );

            $html = '
                <h2>Tekvito Password Reset</h2>
                <p>Hello ' .
                htmlspecialchars($user['first_name'], ENT_QUOTES, 'UTF-8') .
                ',</p>
                <p>We received a request to reset your Tekvito password.</p>
                <p>
                    <a href="' . $safeUrl . '">
                        Reset Your Password
                    </a>
                </p>
                <p>This link expires in 30 minutes.</p>
                <p>If you did not request this, you can ignore this email.</p>
                <p>Tekvito Support</p>
            ';

            if (!send_email(
                $user['email'],
                'Reset Your Tekvito Password',
                $html
            )) {
                // A failed email must not leave a usable token.
                $stmt = $pdo->prepare(
                    'UPDATE password_reset_tokens
                     SET used_at = NOW()
                     WHERE token_hash = ? AND used_at IS NULL'
                );
                $stmt->execute([$tokenHash]);
            }

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('Password reset request failed: ' . $e->getMessage());
        }

        json_out($response);
    }

    public static function resetPassword(): void
    {
        $data = read_json();

        $token = trim((string) ($data['token'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        if ($token === '' || strlen($token) !== 64) {
            json_out([
                'ok' => false,
                'error' => 'Invalid or expired reset link.'
            ], 400);
        }

        if (strlen($password) < 8 || strlen($password) > 72) {
            json_out([
                'ok' => false,
                'error' => 'Password must be between 8 and 72 bytes.'
            ], 422);
        }

        $tokenHash = hash('sha256', $token);
        $pdo = db();

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                "SELECT prt.id, prt.user_id
                 FROM password_reset_tokens prt
                 INNER JOIN users u ON u.id = prt.user_id
                 WHERE prt.token_hash = ?
                   AND prt.used_at IS NULL
                   AND prt.expires_at > NOW()
                   AND u.deleted_at IS NULL
                   AND u.account_status = 'active'
                 LIMIT 1
                 FOR UPDATE"
            );

            $stmt->execute([$tokenHash]);
            $reset = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$reset) {
                $pdo->rollBack();

                json_out([
                    'ok' => false,
                    'error' => 'Invalid or expired reset link.'
                ], 400);
            }

            $passwordHash = password_hash(
                $password,
                PASSWORD_BCRYPT,
                ['cost' => 12]
            );

            if ($passwordHash === false) {
                throw new RuntimeException('Password hashing failed.');
            }

            $stmt = $pdo->prepare(
                'UPDATE users
                 SET password_hash = ?, updated_at = CURRENT_TIMESTAMP
                 WHERE id = ?'
            );

            $stmt->execute([
                $passwordHash,
                (int) $reset['user_id']
            ]);

            // Mark this token and all other outstanding tokens as used.
            $stmt = $pdo->prepare(
                'UPDATE password_reset_tokens
                 SET used_at = NOW()
                 WHERE user_id = ? AND used_at IS NULL'
            );

            $stmt->execute([(int) $reset['user_id']]);

            $pdo->commit();

            json_out([
                'ok' => true,
                'message' => 'Your password has been reset successfully.'
            ]);

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('Password reset failed: ' . $e->getMessage());

            json_out([
                'ok' => false,
                'error' => 'Unable to reset your password. Please try again.'
            ], 500);
        }
    }

    public static function me(): void
    {
        $payload = require_auth();
        $user = User::findById((int) $payload['sub']);
        if (!$user) {
            json_out(['ok' => false, 'error' => 'User not found'], 404);
        }
        $user['roles'] = User::getRoles((int) $user['id']);
        $user['permissions'] = User::getPermissions((int) $user['id']);
        json_out(['ok' => true, 'user' => $user]);
    }
}