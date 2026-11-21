<?php
// backend/controllers/SavedJobController.php

declare(strict_types=1);

final class SavedJobController
{
    public static function index(): void
    {
        $payload = require_role('candidate');
        json_out(['ok' => true, 'data' => SavedJob::listFor((int) $payload['sub'])]);
    }

    public static function save(int $jobId): void
    {
        $payload = require_role('candidate');
        $job = Job::findById($jobId);
        if (!$job) {
            json_out(['ok' => false, 'error' => 'Job not found'], 404);
        }
        SavedJob::save((int) $payload['sub'], $jobId);
        json_out(['ok' => true, 'saved' => true]);
    }

    public static function unsave(int $jobId): void
    {
        $payload = require_role('candidate');
        SavedJob::unsave((int) $payload['sub'], $jobId);
        json_out(['ok' => true, 'saved' => false]);
    }

    public static function isSaved(int $jobId): void
    {
        $payload = require_auth();
        json_out(['ok' => true, 'saved' => SavedJob::isSaved((int) $payload['sub'], $jobId)]);
    }
}