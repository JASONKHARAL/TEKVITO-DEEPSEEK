<?php
// backend/models/TalentRequest.php

declare(strict_types=1);

final class TalentRequest
{
    public static function create(array $data): int
    {
        $stmt = db()->prepare(
            'INSERT INTO talent_requests
             (contact_name, contact_email, contact_phone, company_name, job_title, job_description,
              required_skills, location, country, budget, timeline, preferred_contact)
             VALUES
             (:name, :email, :phone, :company, :title, :desc,
              :skills, :loc, :country, :budget, :timeline, :contact)'
        );
        $stmt->execute([
            ':name'      => $data['contact_name'],
            ':email'     => $data['contact_email'],
            ':phone'     => $data['contact_phone'] ?? null,
            ':company'   => $data['company_name'],
            ':title'     => $data['job_title'],
            ':desc'      => $data['job_description'],
            ':skills'    => $data['required_skills'] ?? null,
            ':loc'       => $data['location'] ?? null,
            ':country'   => $data['country'] ?? null,
            ':budget'    => $data['budget'] ?? null,
            ':timeline'  => $data['timeline'] ?? 'flexible',
            ':contact'   => $data['preferred_contact'] ?? 'email',
        ]);
        return (int) db()->lastInsertId();
    }

    public static function all(array $filters = []): array
    {
        $where = [];
        $params = [];
        if (!empty($filters['status'])) {
            $where[] = 'tr.status = :status';
            $params[':status'] = $filters['status'];
        }
        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $stmt = db()->prepare(
            "SELECT tr.*,
                    u.first_name AS reviewer_first, u.last_name AS reviewer_last
             FROM talent_requests tr
             LEFT JOIN users u ON u.id = tr.reviewed_by
             $whereSql
             ORDER BY tr.created_at DESC
             LIMIT 500"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = db()->prepare(
            'SELECT tr.*,
                    u.first_name AS reviewer_first, u.last_name AS reviewer_last,
                    j.title AS created_job_title, j.slug AS created_job_slug
             FROM talent_requests tr
             LEFT JOIN users u ON u.id = tr.reviewed_by
             LEFT JOIN jobs j ON j.id = tr.created_job_id
             WHERE tr.id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function updateStatus(int $id, string $status, ?string $notes, int $reviewedBy): bool
    {
        $allowed = ['new', 'in_progress', 'posted', 'closed'];
        if (!in_array($status, $allowed, true)) {
            throw new InvalidArgumentException('Invalid status');
        }

        $stmt = db()->prepare(
            'UPDATE talent_requests
             SET status = :s, admin_notes = COALESCE(:notes, admin_notes),
                 reviewed_by = :by, reviewed_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            ':s'     => $status,
            ':notes' => $notes,
            ':by'    => $reviewedBy,
            ':id'    => $id,
        ]);
        return $stmt->rowCount() > 0;
    }

    public static function linkJob(int $id, int $jobId): void
    {
        db()->prepare(
            'UPDATE talent_requests SET created_job_id = :jid, status = "posted", reviewed_at = NOW() WHERE id = :id'
        )->execute([':jid' => $jobId, ':id' => $id]);
    }
}