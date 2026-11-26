<?php
// backend/models/EmailVerification.php

declare(strict_types=1);

final class EmailVerification
{
    public static function issue(int $userId): string
    {
        $raw = bin2hex(random_bytes(32));
        $hash = hash('sha256', $raw);

        db()->prepare(
            'UPDATE email_verification_tokens SET used_at = NOW()
             WHERE user_id = :uid AND used_at IS NULL'
        )->execute([':uid' => $userId]);

        db()->prepare(
            'INSERT INTO email_verification_tokens (user_id, token_hash, expires_at)
             VALUES (:uid, :hash, DATE_ADD(NOW(), INTERVAL 24 HOUR))'
        )->execute([':uid' => $userId, ':hash' => $hash]);

        return $raw;
    }

    public static function verify(string $rawToken): ?int
    {
        $hash = hash('sha256', $rawToken);

        $stmt = db()->prepare(
            'SELECT id, user_id FROM email_verification_tokens
             WHERE token_hash = :h AND used_at IS NULL AND expires_at > NOW()
             LIMIT 1'
        );
        $stmt->execute([':h' => $hash]);
        $row = $stmt->fetch();
        if (!$row) return null;

        $pdo = db();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE email_verification_tokens SET used_at = NOW() WHERE id = :id')
                ->execute([':id' => $row['id']]);

            $pdo->prepare(
                "UPDATE users
                 SET email_verified_at = NOW(),
                     account_status = CASE WHEN account_status = 'pending' THEN 'active' ELSE account_status END
                 WHERE id = :uid"
            )->execute([':uid' => $row['user_id']]);

            $pdo->commit();
            return (int) $row['user_id'];
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}