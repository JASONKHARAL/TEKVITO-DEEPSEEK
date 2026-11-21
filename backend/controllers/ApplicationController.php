<?php
// backend/controllers/ApplicationController.php

declare(strict_types=1);

final class ApplicationController
{
    public static function apply(int $jobId): void
    {
        $payload = require_role('candidate');
        $userId = (int) $payload['sub'];
        $data = read_json();
        $coverLetter = isset($data['cover_letter']) ? trim((string) $data['cover_letter']) : null;
        if ($coverLetter === '') $coverLetter = null;

        $job = Job::findById($jobId);
        if (!$job) {
            json_out(['ok' => false, 'error' => 'Job not found'], 404);
        }
        if ($job['status'] !== 'published') {
            json_out(['ok' => false, 'error' => 'Job is not accepting applications'], 422);
        }

        $profile = CandidateProfile::getOrCreate($userId);
        if (Application::hasApplied($jobId, (int) $profile['id'])) {
            json_out(['ok' => false, 'error' => 'You have already applied to this job'], 409);
        }

        // Require a primary resume
        $resumes = Resume::listFor((int) $profile['id']);
        if (empty($resumes)) {
            json_out([
                'ok' => false,
                'error' => 'You must upload a resume before applying',
                'code'  => 'RESUME_REQUIRED',
            ], 422);
        }
        $primary = null;
        foreach ($resumes as $r) {
            if (!empty($r['is_primary'])) { $primary = $r; break; }
        }
        if (!$primary) $primary = $resumes[0];

        $appId = Application::create($jobId, (int) $profile['id'], $coverLetter, (int) $primary['id']);

        // Notify job owner
        $jobOwner = (int) $job['created_by'];
        if ($jobOwner && $jobOwner !== $userId) {
            Notification::create(
                $jobOwner,
                'application_received',
                'New application received',
                "{$job['title']} — a candidate just applied",
                '/received-applications/' . $appId
            );
        }

        $app = Application::findById($appId, (int) $profile['id']);
        json_out(['ok' => true, 'application' => $app], 201);
    }

    public static function index(): void
    {
        $payload = require_role('candidate');
        $profile = CandidateProfile::getOrCreate((int) $payload['sub']);
        json_out(['ok' => true, 'data' => Application::listForCandidate((int) $profile['id'])]);
    }

    public static function show(int $id): void
    {
        $payload = require_role('candidate');
        $profile = CandidateProfile::getOrCreate((int) $payload['sub']);
        $app = Application::findById($id, (int) $profile['id']);
        if (!$app) {
            json_out(['ok' => false, 'error' => 'Application not found'], 404);
        }
        json_out(['ok' => true, 'application' => $app]);
    }

    public static function hasApplied(int $jobId): void
    {
        $payload = require_auth();
        $profile = CandidateProfile::getOrCreate((int) $payload['sub']);
        json_out(['ok' => true, 'has_applied' => Application::hasApplied($jobId, (int) $profile['id'])]);
    }

    public static function withdraw(int $id): void
    {
        $payload = require_role('candidate');
        $profile = CandidateProfile::getOrCreate((int) $payload['sub']);

        $GLOBALS['__withdraw_user_id'] = (int) $payload['sub'];
        try {
            $ok = Application::withdraw($id, (int) $profile['id']);
        } finally {
            unset($GLOBALS['__withdraw_user_id']);
        }
        if (!$ok) {
            json_out(['ok' => false, 'error' => 'Application not found'], 404);
        }

        // Notify job owner of withdrawal
        $info = db()->prepare(
            "SELECT j.created_by, j.title FROM jobs j
             JOIN applications a ON a.job_id = j.id
             WHERE a.id = :id LIMIT 1"
        );
        $info->execute([':id' => $id]);
        $row = $info->fetch();
        if ($row && (int) $row['created_by'] !== (int) $payload['sub']) {
            Notification::create(
                (int) $row['created_by'],
                'application_withdrawn',
                'Application withdrawn',
                "A candidate withdrew from '{$row['title']}'",
                '/received-applications/' . $id
            );
        }

        $app = Application::findById($id, (int) $profile['id']);
        json_out(['ok' => true, 'application' => $app]);
    }
}