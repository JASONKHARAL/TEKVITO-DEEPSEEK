<?php
// backend/models/SavedJob.php

declare(strict_types=1);

final class SavedJob
{
    public static function save(int $userId, int $jobId): bool
    {
        $stmt = db()->prepare(
            'INSERT IGNORE INTO saved_jobs (user_id, job_id) VALUES (:uid, :jid)'
        );
        $stmt->execute([':uid' => $userId, ':jid' => $jobId]);
        return $stmt->rowCount() > 0;
    }

    public static function unsave(int $userId, int $jobId): bool
    {
        $stmt = db()->prepare('DELETE FROM saved_jobs WHERE user_id = :uid AND job_id = :jid');
        $stmt->execute([':uid' => $userId, ':jid' => $jobId]);
        return $stmt->rowCount() > 0;
    }

    public static function isSaved(int $userId, int $jobId): bool
    {
        $stmt = db()->prepare('SELECT 1 FROM saved_jobs WHERE user_id = :uid AND job_id = :jid LIMIT 1');
        $stmt->execute([':uid' => $userId, ':jid' => $jobId]);
        return (bool) $stmt->fetchColumn();
    }

    public static function listFor(int $userId): array
    {
        $stmt = db()->prepare(
            'SELECT j.id, j.title, j.slug, j.employment_type, j.workplace_type,
                    j.location, j.country, j.salary_min, j.salary_max, j.salary_currency,
                    j.status, j.published_at, j.created_at,
                    c.name AS category_name,
                    sj.created_at AS saved_at
             FROM saved_jobs sj
             JOIN jobs j ON j.id = sj.job_id
             LEFT JOIN job_categories c ON c.id = j.category_id
             WHERE sj.user_id = :uid
             ORDER BY sj.created_at DESC'
        );
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll();
    }
}