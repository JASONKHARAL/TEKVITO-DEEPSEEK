<?php
// backend/models/Notification.php

declare(strict_types=1);

final class Notification
{
    public static function create(int $userId, string $type, string $title, string $message, ?string $actionUrl = null): int
    {
        $stmt = db()->prepare(
            'INSERT INTO notifications (user_id, notification_type, title, message, action_url)
             VALUES (:uid, :type, :title, :msg, :url)'
        );
        $stmt->execute([
            ':uid'   => $userId,
            ':type'  => $type,
            ':title' => $title,
            ':msg'   => $message,
            ':url'   => $actionUrl,
        ]);
        return (int) db()->lastInsertId();
    }

    public static function listFor(int $userId, int $limit = 50): array
    {
        $stmt = db()->prepare(
            'SELECT id, notification_type, title, message, action_url, read_at, created_at
             FROM notifications
             WHERE user_id = :uid
             ORDER BY created_at DESC
             LIMIT :lim'
        );
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function unreadCount(int $userId): int
    {
        $stmt = db()->prepare(
            'SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND read_at IS NULL'
        );
        $stmt->execute([':uid' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public static function markRead(int $id, int $userId): bool
    {
        $stmt = db()->prepare(
            'UPDATE notifications SET read_at = NOW() WHERE id = :id AND user_id = :uid AND read_at IS NULL'
        );
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        return $stmt->rowCount() > 0;
    }

    public static function markAllRead(int $userId): int
    {
        $stmt = db()->prepare(
            'UPDATE notifications SET read_at = NOW() WHERE user_id = :uid AND read_at IS NULL'
        );
        $stmt->execute([':uid' => $userId]);
        return $stmt->rowCount();
    }
}