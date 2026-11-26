<?php
// backend/controllers/EmailVerificationController.php

declare(strict_types=1);

final class EmailVerificationController
{
    public static function verify(): void
    {
        $token = trim((string) ($_GET['token'] ?? ''));
        if ($token === '') {
            json_out(['ok' => false, 'error' => 'Missing token'], 422);
        }

        $userId = EmailVerification::verify($token);
        if (!$userId) {
            json_out(['ok' => false, 'error' => 'Invalid or expired verification link'], 400);
        }

        $user = User::findById($userId);
        json_out(['ok' => true, 'message' => 'Email verified! You can now log in.', 'email' => $user['email'] ?? null]);
    }

    public static function resend(): void
    {
        $data = read_json();
        $email = trim((string) ($data['email'] ?? ''));
        if ($email === '') {
            json_out(['ok' => false, 'error' => 'Email is required'], 422);
        }

        $user = User::findByEmail($email);
        if (!$user) {
            json_out(['ok' => true, 'message' => 'If this email exists, a verification link has been sent.']);
        }

        if (!empty($user['email_verified_at'])) {
            json_out(['ok' => true, 'message' => 'This email is already verified. You can log in.']);
        }

        $raw = EmailVerification::issue((int) $user['id']);
        $sent = EmailService::sendVerification(
            (int) $user['id'],
            $user['email'],
            $user['first_name'],
            $raw
        );

        if (!$sent) {
            json_out([
                'ok' => false,
                'error' => 'Could not send verification email. Check SMTP settings or try again later.',
            ], 500);
        }

        json_out(['ok' => true, 'message' => 'Verification link sent to ' . $user['email']]);
    }
}