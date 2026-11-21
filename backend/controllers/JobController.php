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
}
