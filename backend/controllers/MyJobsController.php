<?php
// backend/controllers/MyJobsController.php

declare(strict_types=1);

final class MyJobsController
{
    public static function index(): void
    {
        $payload = require_auth();
        $userId = (int) $payload['sub'];

        $stmt = db()->prepare(
            'SELECT j.id, j.title, j.slug, j.status, j.employment_type, j.workplace_type,
                    j.location, j.country, j.salary_min, j.salary_max, j.salary_currency,
                    j.published_at, j.created_at, j.updated_at,
                    c.name AS category_name
             FROM jobs j
             LEFT JOIN job_categories c ON c.id = j.category_id
             WHERE j.created_by = :uid
             ORDER BY j.updated_at DESC, j.created_at DESC'
        );
        $stmt->execute([':uid' => $userId]);
        $jobs = $stmt->fetchAll();

        json_out(['ok' => true, 'data' => $jobs]);
    }

    public static function show(int $id): void
    {
        $payload = require_auth();
        $userId = (int) $payload['sub'];

        $job = Job::findById($id);
        if (!$job) {
            json_out(['ok' => false, 'error' => 'Job not found'], 404);
        }
        if ((int) $job['created_by'] !== $userId) {
            // allow admins (jobs.approve permission)
            $perms = User::getPermissions($userId);
            if (!in_array('jobs.approve', $perms, true)) {
                json_out(['ok' => false, 'error' => 'Not allowed to view this job'], 403);
            }
        }

        json_out(['ok' => true, 'job' => $job]);
    }
}