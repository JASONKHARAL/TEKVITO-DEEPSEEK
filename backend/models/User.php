<?php
// backend/models/User.php

declare(strict_types=1);

final class User
{
    public static function findByEmail(string $email): ?array
    {
        $stmt = db()->prepare(
            'SELECT id, email, password_hash, first_name, last_name, phone,
                    account_status, email_verified_at, created_at
             FROM users
             WHERE email = :email AND deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findById(int $id): ?array
    {
        $stmt = db()->prepare(
            'SELECT id, email, first_name, last_name, phone,
                    account_status, email_verified_at, created_at
             FROM users
             WHERE id = :id AND deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function getRoles(int $userId): array
    {
        $stmt = db()->prepare(
            'SELECT r.name
             FROM user_roles ur
             JOIN roles r ON r.id = ur.role_id
             WHERE ur.user_id = :uid'
        );
        $stmt->execute([':uid' => $userId]);
        return array_column($stmt->fetchAll(), 'name');
    }

    public static function getPermissions(int $userId): array
    {
        $stmt = db()->prepare(
            'SELECT DISTINCT p.permission_key
             FROM user_roles ur
             JOIN role_permissions rp ON rp.role_id = ur.role_id
             JOIN permissions p ON p.id = rp.permission_id
             WHERE ur.user_id = :uid'
        );
        $stmt->execute([':uid' => $userId]);
        return array_column($stmt->fetchAll(), 'permission_key');
    }

    public static function verifyPassword(string $email, string $password): ?array
    {
        $user = self::findByEmail($email);
        if (!$user) return null;
        if (!password_verify($password, $user['password_hash'])) return null;
        if ($user['account_status'] !== 'active') return null;
        unset($user['password_hash']);
        return $user;
    }

    public static function create(
        string $email,
        string $password,
        string $firstName,
        string $lastName,
        string $defaultRole = 'candidate'
    ): int {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO users (email, password_hash, first_name, last_name, account_status)
                 VALUES (:email, :hash, :first, :last, :status)'
            );
            $stmt->execute([
                ':email'  => $email,
                ':hash'   => $hash,
                ':first'  => $firstName,
                ':last'   => $lastName,
                ':status' => 'active',
            ]);
            $userId = (int) $pdo->lastInsertId();

            $roleStmt = $pdo->prepare(
                'INSERT IGNORE INTO user_roles (user_id, role_id)
                 SELECT :uid, id FROM roles WHERE name = :role'
            );
            $roleStmt->execute([':uid' => $userId, ':role' => $defaultRole]);

            $pdo->commit();
            return $userId;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}