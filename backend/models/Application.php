<?php
// backend/models/Application.php

declare(strict_types=1);

final class Application
{
    public static function hasApplied(int $jobId, int $candidateId): bool
    {
        $stmt = db()->prepare(
            'SELECT 1 FROM applications WHERE job_id = :j AND candidate_id = :c LIMIT 1'
        );
        $stmt->execute([':j' => $jobId, ':c' => $candidateId]);
        return (bool) $stmt->fetchColumn();
    }

    public static function create(int $jobId, int $candidateId, ?string $coverLetter, int $resumeId): int
    {
        $stmt = db()->prepare(
            'INSERT INTO applications (job_id, candidate_id, resume_id, status, cover_letter)
             VALUES (:j, :c, :r, :status, :cover)'
        );
        $stmt->execute([
            ':j'      => $jobId,
            ':c'      => $candidateId,
            ':r'      => $resumeId,
            ':status' => 'submitted',
            ':cover'  => $coverLetter,
        ]);
        return (int) db()->lastInsertId();
    }

    public static function listForCandidate(int $candidateId): array
    {
        $stmt = db()->prepare(
            'SELECT a.id, a.status, a.applied_at, a.updated_at, a.resume_id,
                    j.id AS job_id, j.title AS job_title, j.location, j.country,
                    j.workplace_type, j.employment_type,
                    c.name AS category_name
             FROM applications a
             JOIN jobs j ON j.id = a.job_id
             LEFT JOIN job_categories c ON c.id = j.category_id
             WHERE a.candidate_id = :cid
             ORDER BY a.applied_at DESC'
        );
        $stmt->execute([':cid' => $candidateId]);
        return $stmt->fetchAll();
    }

    public static function findById(int $id, int $candidateId): ?array
    {
        $stmt = db()->prepare(
            'SELECT a.*, j.title AS job_title, j.description, j.location, j.country,
                    j.workplace_type, j.employment_type,
                    c.name AS category_name,
                    r.original_filename AS resume_filename
             FROM applications a
             JOIN jobs j ON j.id = a.job_id
             LEFT JOIN job_categories c ON c.id = j.category_id
             LEFT JOIN candidate_resumes r ON r.id = a.resume_id
             WHERE a.id = :id AND a.candidate_id = :cid
             LIMIT 1'
        );
        $stmt->execute([':id' => $id, ':cid' => $candidateId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function withdraw(int $id, int $candidateId): bool
    {
        $app = self::findById($id, $candidateId);
        if (!$app) return false;

        $withdrawable = ['submitted', 'under_review', 'shortlisted'];
        if (!in_array($app['status'], $withdrawable, true)) {
            throw new RuntimeException("Cannot withdraw an application in '{$app['status']}' state");
        }

        $pdo = db();
        $pdo->beginTransaction();
        try {
            $pdo->prepare("UPDATE applications SET status = 'withdrawn', updated_at = NOW() WHERE id = :id")
                ->execute([':id' => $id]);

            $pdo->prepare(
                'INSERT INTO application_status_history
                 (application_id, previous_status, new_status, changed_by, notes)
                 VALUES (:app, :prev, :new, :by, :notes)'
            )->execute([
                ':app'  => $id,
                ':prev' => $app['status'],
                ':new'  => 'withdrawn',
                ':by'   => (int) (self::currentUserId() ?? 0),
                ':notes' => 'Withdrawn by candidate',
            ]);

            $pdo->commit();
            return true;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private static function currentUserId(): ?int
    {
        // Not ideal — pass user id from controller instead.
        return $GLOBALS['__withdraw_user_id'] ?? null;
    }
}