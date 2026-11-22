<?php
// backend/controllers/JobController.php

declare(strict_types=1);

final class JobController
{
    public static function index(): void
    {
        $filters = [
            'status'      => $_GET['status']      ?? 'published',
            'category_id' => $_GET['category_id'] ?? null,
            'country'     => $_GET['country']     ?? null,
            'q'           => $_GET['q']           ?? null,
            'skill_id'    => $_GET['skill_id']    ?? null,
            'page'        => $_GET['page']        ?? 1,
            'per_page'    => $_GET['per_page']    ?? 20,
        ];

        $result = Job::list($filters);
        json_out(['ok' => true] + $result);
    }

    public static function show(int $id): void
    {
        $job = Job::findById($id);
        if (!$job) {
            json_out(['ok' => false, 'error' => 'Job not found'], 404);
        }
        json_out(['ok' => true, 'job' => $job]);
    }

    public static function categories(): void
    {
        json_out(['ok' => true, 'categories' => Job::categories()]);
    }

    public static function store(): void
    {
        $payload = require_permission('jobs.create');
        $data = read_json();

        $title = trim((string) ($data['title'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));

        if ($title === '' || $description === '') {
            json_out(['ok' => false, 'error' => 'Title and description are required'], 422);
        }

        $id = Job::create($data, (int) $payload['sub']);
        $job = Job::findById($id);

        json_out(['ok' => true, 'job' => $job], 201);
    }

    public static function updateStatus(int $id): void
    {
        $payload = require_permission('jobs.approve');
        $data = read_json();
        $status = trim((string) ($data['status'] ?? ''));

        if ($status === '') {
            json_out(['ok' => false, 'error' => 'Status is required'], 422);
        }

        try {
            Job::updateStatus($id, $status, (int) $payload['sub'], $data['notes'] ?? null);
        } catch (InvalidArgumentException $e) {
            json_out(['ok' => false, 'error' => $e->getMessage()], 422);
        } catch (RuntimeException $e) {
            json_out(['ok' => false, 'error' => $e->getMessage()], 404);
        }

        $job = Job::findById($id);
        json_out(['ok' => true, 'job' => $job]);
    }

    public static function update(int $id): void
    {
        $payload = require_auth();
        $userId = (int) $payload['sub'];
        $roles = $payload['roles'] ?? [];
        $isAdmin = in_array('super_admin', $roles, true) || in_array('recruiter', $roles, true);

        $job = Job::findById($id);
        if (!$job) {
            json_out(['ok' => false, 'error' => 'Job not found'], 404);
        }
        if (!$isAdmin && (int) $job['created_by'] !== $userId) {
            json_out(['ok' => false, 'error' => 'You do not own this job'], 403);
        }

        $data = read_json();
        if (isset($data['title']) && trim((string) $data['title']) === '') {
            json_out(['ok' => false, 'error' => 'Title cannot be empty'], 422);
        }
        if (isset($data['description']) && trim((string) $data['description']) === '') {
            json_out(['ok' => false, 'error' => 'Description cannot be empty'], 422);
        }

        try {
            $updated = Job::update($id, $data, $userId);
        } catch (RuntimeException $e) {
            json_out(['ok' => false, 'error' => $e->getMessage()], 404);
        }

        json_out(['ok' => true, 'job' => $updated]);
    }

    public static function versions(int $id): void
    {
        $payload = require_auth();
        $userId = (int) $payload['sub'];
        $roles = $payload['roles'] ?? [];
        $isAdmin = in_array('super_admin', $roles, true) || in_array('recruiter', $roles, true);

        $job = Job::findById($id);
        if (!$job) {
            json_out(['ok' => false, 'error' => 'Job not found'], 404);
        }
        if (!$isAdmin && (int) $job['created_by'] !== $userId) {
            json_out(['ok' => false, 'error' => 'You do not own this job'], 403);
        }

        json_out(['ok' => true, 'versions' => Job::versions($id)]);
    }

    public static function versionDetail(int $id, int $versionNumber): void
    {
        $payload = require_auth();
        $userId = (int) $payload['sub'];
        $roles = $payload['roles'] ?? [];
        $isAdmin = in_array('super_admin', $roles, true) || in_array('recruiter', $roles, true);

        $job = Job::findById($id);
        if (!$job) {
            json_out(['ok' => false, 'error' => 'Job not found'], 404);
        }
        if (!$isAdmin && (int) $job['created_by'] !== $userId) {
            json_out(['ok' => false, 'error' => 'You do not own this job'], 403);
        }

        $version = Job::versionDetail($id, $versionNumber);
        if (!$version) {
            json_out(['ok' => false, 'error' => 'Version not found'], 404);
        }
        json_out(['ok' => true, 'version' => $version]);
    }

    public static function getSkills(int $jobId): void
    {
        $stmt = db()->prepare(
            'SELECT s.id, s.name, js.is_required
             FROM job_skills js
             JOIN skills s ON s.id = js.skill_id
             WHERE js.job_id = :jid
             ORDER BY s.name'
        );
        $stmt->execute([':jid' => $jobId]);
        json_out(['ok' => true, 'skills' => $stmt->fetchAll()]);
    }

    public static function setSkills(int $jobId): void
    {
        $payload = require_auth();
        $userId = (int) $payload['sub'];
        $roles = $payload['roles'] ?? [];
        $isAdmin = in_array('super_admin', $roles, true) || in_array('recruiter', $roles, true);

        $job = Job::findById($jobId);
        if (!$job) {
            json_out(['ok' => false, 'error' => 'Job not found'], 404);
        }
        if (!$isAdmin && (int) $job['created_by'] !== $userId) {
            json_out(['ok' => false, 'error' => 'You do not own this job'], 403);
        }

        $data = read_json();
        $skillIds = $data['skill_ids'] ?? [];
        if (!is_array($skillIds)) {
            json_out(['ok' => false, 'error' => 'skill_ids must be an array'], 422);
        }

        $pdo = db();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM job_skills WHERE job_id = :jid')->execute([':jid' => $jobId]);
            if (!empty($skillIds)) {
                $insert = $pdo->prepare(
                    'INSERT INTO job_skills (job_id, skill_id, is_required) VALUES (:jid, :sid, 1)'
                );
                foreach ($skillIds as $sid) {
                    $insert->execute([':jid' => $jobId, ':sid' => (int) $sid]);
                }
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        self::getSkills($jobId);
    }

    public static function destroy(int $id): void
    {
        $payload = require_auth();
        $userId = (int) $payload['sub'];
        $roles = $payload['roles'] ?? [];
        $isAdmin = in_array('super_admin', $roles, true) || in_array('admin', $roles, true);

        $job = Job::findById($id);
        if (!$job) {
            json_out(['ok' => false, 'error' => 'Job not found'], 404);
        }
        
        if (!$isAdmin && (int) $job['created_by'] !== $userId) {
            json_out(['ok' => false, 'error' => 'Unauthorized to delete this job'], 403);
        }

        // SOFT DELETE: Just set status to closed so applications aren't orphaned
        $stmt = db()->prepare('UPDATE jobs SET status = "closed" WHERE id = :id');
        $stmt->execute([':id' => $id]);

        json_out(['ok' => true, 'message' => 'Job closed successfully']);
    }
}