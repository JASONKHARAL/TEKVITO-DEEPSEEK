<?php
// backend/controllers/ReceivedApplicationsController.php

declare(strict_types=1);

final class ReceivedApplicationsController
{
    private const FLOW = [
        'submitted'    => ['under_review', 'rejected'],
        'under_review' => ['shortlisted', 'rejected'],
        'shortlisted'  => ['interview', 'rejected'],
        'interview'    => ['offered', 'rejected'],
        'offered'      => ['hired', 'rejected'],
        'hired'        => [],
        'rejected'     => [],
        'withdrawn'    => [],
    ];

    public static function index(): void
    {
        $payload = require_role('super_admin', 'recruiter');
        $userId = (int) $payload['sub'];
        $isSuperAdmin = in_array('super_admin', $payload['roles'] ?? [], true);

        if ($isSuperAdmin) {
            $stmt = db()->prepare(
                'SELECT a.id, a.status, a.applied_at, a.updated_at, a.resume_id,
                        j.id AS job_id, j.title AS job_title,
                        u.id AS candidate_user_id, u.first_name, u.last_name, u.email,
                        cp.headline, cp.location, cp.country
                 FROM applications a
                 JOIN jobs j ON j.id = a.job_id
                 JOIN candidate_profiles cp ON cp.id = a.candidate_id
                 JOIN users u ON u.id = cp.user_id
                 ORDER BY a.applied_at DESC'
            );
            $stmt->execute();
        } else {
            $stmt = db()->prepare(
                'SELECT a.id, a.status, a.applied_at, a.updated_at, a.resume_id,
                        j.id AS job_id, j.title AS job_title,
                        u.id AS candidate_user_id, u.first_name, u.last_name, u.email,
                        cp.headline, cp.location, cp.country
                 FROM applications a
                 JOIN jobs j ON j.id = a.job_id
                 JOIN candidate_profiles cp ON cp.id = a.candidate_id
                 JOIN users u ON u.id = cp.user_id
                 WHERE j.created_by = :uid
                 ORDER BY a.applied_at DESC'
            );
            $stmt->execute([':uid' => $userId]);
        }

        json_out(['ok' => true, 'data' => $stmt->fetchAll()]);
    }

    public static function show(int $id): void
    {
        $payload = require_role('super_admin', 'recruiter');
        $isSuperAdmin = in_array('super_admin', $payload['roles'] ?? [], true);

        $app = self::findWithAccess($id, (int) $payload['sub'], $isSuperAdmin);
        if (!$app) {
            json_out(['ok' => false, 'error' => 'Application not found'], 404);
        }

        $app['allowed_transitions'] = self::FLOW[$app['status']] ?? [];
        json_out(['ok' => true, 'application' => $app]);
    }

    public static function updateStatus(int $id): void
    {
        $payload = require_role('super_admin', 'recruiter');
        $isSuperAdmin = in_array('super_admin', $payload['roles'] ?? [], true);

        $app = self::findWithAccess($id, (int) $payload['sub'], $isSuperAdmin);
        if (!$app) {
            json_out(['ok' => false, 'error' => 'Application not found'], 404);
        }

        $data = read_json();
        $newStatus = trim((string) ($data['status'] ?? ''));
        $notes = isset($data['notes']) ? trim((string) $data['notes']) : null;

        if ($newStatus === '') {
            json_out(['ok' => false, 'error' => 'Status is required'], 422);
        }

        $allowed = self::FLOW[$app['status']] ?? [];
        if (!in_array($newStatus, $allowed, true)) {
            json_out([
                'ok' => false,
                'error' => "Cannot change from '{$app['status']}' to '$newStatus'",
                'allowed' => $allowed,
            ], 422);
        }

        $pdo = db();
        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                'UPDATE applications SET status = :status, updated_at = NOW() WHERE id = :id'
            )->execute([':status' => $newStatus, ':id' => $id]);

            $pdo->prepare(
                'INSERT INTO application_status_history
                 (application_id, previous_status, new_status, changed_by, notes)
                 VALUES (:app, :prev, :new, :by, :notes)'
            )->execute([
                ':app'   => $id,
                ':prev'  => $app['status'],
                ':new'   => $newStatus,
                ':by'    => (int) $payload['sub'],
                ':notes' => $notes,
            ]);

            $pdo->commit();
            // Notify the candidate
$candidateUserId = (int) $pdo->query(
    "SELECT cp.user_id FROM candidate_profiles cp
     JOIN applications a ON a.candidate_id = cp.id
     WHERE a.id = " . $id . " LIMIT 1"
)->fetchColumn();

if ($candidateUserId) {
    $statusLabel = ucwords(str_replace('_', ' ', $newStatus));
    Notification::create(
        $candidateUserId,
        'application_status',
        'Application update',
        "Your application for '{$app['job_title']}' is now: $statusLabel",
        '/applications/' . $id
    );
}
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        $updated = self::findWithAccess($id, (int) $payload['sub'], $isSuperAdmin);
        $updated['allowed_transitions'] = self::FLOW[$updated['status']] ?? [];

        json_out(['ok' => true, 'application' => $updated]);
    }

    private static function findWithAccess(int $id, int $userId, bool $isSuperAdmin): ?array
    {
        $sql = 'SELECT a.*, j.title AS job_title, j.id AS job_id,
                       j.created_by AS job_owner_id,
                       r.original_filename AS resume_filename,
                       cp.user_id AS candidate_user_id,
                       cp.headline, cp.location AS candidate_location,
                       cp.country AS candidate_country, cp.years_experience,
                       cp.linkedin_url, cp.portfolio_url,
                       u.first_name, u.last_name, u.email AS candidate_email
                FROM applications a
                JOIN jobs j ON j.id = a.job_id
                JOIN candidate_profiles cp ON cp.id = a.candidate_id
                JOIN users u ON u.id = cp.user_id
                LEFT JOIN candidate_resumes r ON r.id = a.resume_id
                WHERE a.id = :id
                LIMIT 1';

        $stmt = db()->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }
        if (!$isSuperAdmin && (int) $row['job_owner_id'] !== $userId) {
            return null;
        }

        // Load candidate skills AFTER $row is set
        $skillStmt = db()->prepare(
            'SELECT s.id, s.name, cs.proficiency, cs.years_experience
             FROM candidate_skills cs
             JOIN skills s ON s.id = cs.skill_id
             WHERE cs.candidate_id = :cid
             ORDER BY s.name'
        );
        $skillStmt->execute([':cid' => $row['candidate_id']]);
        $row['skills'] = $skillStmt->fetchAll();

        return $row;
    }
}