<?php
// backend/controllers/NotificationController.php

declare(strict_types=1);

final class NotificationController
{
    public static function index(): void
    {
        $payload = require_auth();
        $userId = (int) $payload['sub'];
        json_out([
            'ok'             => true,
            'data'           => Notification::listFor($userId),
            'unread_count'   => Notification::unreadCount($userId),
        ]);
    }

    public static function unreadCount(): void
    {
        $payload = require_auth();
        json_out(['ok' => true, 'unread_count' => Notification::unreadCount((int) $payload['sub'])]);
    }

    public static function markRead(int $id): void
    {
        $payload = require_auth();
        $ok = Notification::markRead($id, (int) $payload['sub']);
        if (!$ok) {
            json_out(['ok' => false, 'error' => 'Not found or already read'], 404);
        }
        json_out(['ok' => true, 'unread_count' => Notification::unreadCount((int) $payload['sub'])]);
    }

    public static function markAllRead(): void
    {
        $payload = require_auth();
        $count = Notification::markAllRead((int) $payload['sub']);
        json_out(['ok' => true, 'marked' => $count, 'unread_count' => 0]);
    }
}