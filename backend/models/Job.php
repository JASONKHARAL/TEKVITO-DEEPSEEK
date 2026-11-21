<?php
// backend/models/Job.php

declare(strict_types=1);

final class Job
{
    public static function list(array $filters = []): array
    {
        $where = ['j.status = :status'];
        $params = [':status' => $filters['status'] ?? 'published'];

        if (!empty($filters['category_id'])) {
            $where[] = 'j.category_id = :category_id';
            $params[':category_id'] = (int) $filters['category_id'];
        }
        if (!empty($filters['country'])) {
            $where[] = 'j.country = :country';
            $params[':country'] = $filters['country'];
        }
        if (!empty($filters['q'])) {
    $where[] = '(j.title LIKE :q_title OR j.description LIKE :q_desc)';
    $params[':q_title'] = '%' . $filters['q'] . '%';
    $params[':q_desc']  = '%' . $filters['q'] . '%';

        }

        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($filters['per_page'] ?? 20)));
        $offset = ($page - 1) * $perPage;

        $whereSql = implode(' AND ', $where);

        $countStmt = db()->prepare("SELECT COUNT(*) FROM jobs j WHERE $whereSql");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql = "SELECT j.id, j.title, j.slug, j.employment_type, j.workplace_type,
                       j.location, j.country, j.salary_min, j.salary_max, j.salary_currency,
                       j.status, j.published_at, j.created_at,
                       c.name AS category_name
                FROM jobs j
                LEFT JOIN job_categories c ON c.id = j.category_id
                WHERE $whereSql
                ORDER BY j.published_at DESC, j.created_at DESC
                LIMIT $perPage OFFSET $offset";

        $stmt = db()->prepare($sql);
        $stmt->execute($params);

        return [
            'data' => $stmt->fetchAll(),
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => (int) ceil($total / $perPage),
            ],
        ];
    }

    public static function findById(int $id): ?array
    {
        $stmt = db()->prepare(
            'SELECT j.*, c.name AS category_name, u.first_name AS creator_first, u.last_name AS creator_last
             FROM jobs j
             LEFT JOIN job_categories c ON c.id = j.category_id
             LEFT JOIN users u ON u.id = j.created_by
             WHERE j.id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function categories(): array
    {
        $stmt = db()->query(
            'SELECT id, name, slug, description
             FROM job_categories
             WHERE is_active = 1
             ORDER BY name'
        );
        return $stmt->fetchAll();
    }

    public static function create(array $data, int $createdBy): int
    {
        $slugBase = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $data['title']), '-'));
        $slug = $slugBase . '-' . substr(bin2hex(random_bytes(4)), 0, 8);

        $stmt = db()->prepare(
            'INSERT INTO jobs (
                category_id, created_by, title, slug, description,
                responsibilities, requirements, employment_type, workplace_type,
                location, country, salary_min, salary_max, salary_currency, status
             ) VALUES (
                :category_id, :created_by, :title, :slug, :description,
                :responsibilities, :requirements, :employment_type, :workplace_type,
                :location, :country, :salary_min, :salary_max, :salary_currency, :status
             )'
        );

        $stmt->execute([
            ':category_id'      => $data['category_id'] ?? null,
            ':created_by'       => $createdBy,
            ':title'            => $data['title'],
            ':slug'             => $slug,
            ':description'      => $data['description'],
            ':responsibilities' => $data['responsibilities'] ?? null,
            ':requirements'     => $data['requirements'] ?? null,
            ':employment_type'  => $data['employment_type'] ?? 'full_time',
            ':workplace_type'   => $data['workplace_type'] ?? 'onsite',
            ':location'         => $data['location'] ?? null,
            ':country'          => $data['country'] ?? null,
            ':salary_min'       => $data['salary_min'] ?? null,
            ':salary_max'       => $data['salary_max'] ?? null,
            ':salary_currency'  => $data['salary_currency'] ?? null,
            ':status'           => $data['status'] ?? 'draft',
        ]);

        return (int) db()->lastInsertId();
    }

    public static function updateStatus(int $id, string $newStatus, int $changedBy, ?string $notes = null): void
    {
        $allowed = ['draft','pending_approval','approved','published','rejected','closed','archived'];
        if (!in_array($newStatus, $allowed, true)) {
            throw new InvalidArgumentException('Invalid status');
        }

        $pdo = db();
        $pdo->beginTransaction();
        try {
            $current = self::findById($id);
            if (!$current) {
                throw new RuntimeException('Job not found');
            }

            $update = 'UPDATE jobs SET status = :status';
            $params = [':status' => $newStatus, ':id' => $id];

            if ($newStatus === 'published' && $current['status'] !== 'published') {
                $update .= ', published_at = NOW()';
            }
            if ($newStatus === 'approved') {
                $update .= ', approved_by = :approved_by, approved_at = NOW()';
                $params[':approved_by'] = $changedBy;
            }

            $update .= ' WHERE id = :id';
            $pdo->prepare($update)->execute($params);

            $pdo->prepare(
                'INSERT INTO job_status_history (job_id, previous_status, new_status, changed_by, notes)
                 VALUES (:job_id, :prev, :new, :by, :notes)'
            )->execute([
                ':job_id' => $id,
                ':prev'   => $current['status'],
                ':new'    => $newStatus,
                ':by'     => $changedBy,
                ':notes'  => $notes,
            ]);

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function update(int $id, array $data, int $changedBy): array
    {
        $current = self::findById($id);
        if (!$current) {
            throw new RuntimeException('Job not found');
        }

        // Snapshot before update — only if currently published
        if ($current['status'] === 'published') {
            self::snapshot($id, $current, $changedBy, 'Edited published job');
        }

        $allowed = [
            'title','description','responsibilities','requirements',
            'employment_type','workplace_type','location','country',
            'salary_min','salary_max','salary_currency','category_id',
            'expires_at',
        ];

        $set = [];
        $params = [':id' => $id];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $data)) {
                $set[] = "$f = :$f";
                $params[":$f"] = $data[$f];
            }
        }
        if (!$set) {
            return self::findById($id);
        }

        $sql = 'UPDATE jobs SET ' . implode(', ', $set) . ', updated_at = NOW() WHERE id = :id';
        db()->prepare($sql)->execute($params);

        // Log status history (no status change, just an edit)
        db()->prepare(
            'INSERT INTO job_status_history (job_id, previous_status, new_status, changed_by, notes)
             VALUES (:job, :prev, :new, :by, :notes)'
        )->execute([
            ':job'   => $id,
            ':prev'  => $current['status'],
            ':new'   => $current['status'],
            ':by'    => $changedBy,
            ':notes' => 'Job details edited',
        ]);

        return self::findById($id);
    }

    public static function snapshot(int $jobId, array $jobData, int $changedBy, ?string $reason = null): int
    {
        $stmt = db()->prepare(
            'SELECT COALESCE(MAX(version_number), 0) + 1 FROM job_versions WHERE job_id = :jid'
        );
        $stmt->execute([':jid' => $jobId]);
        $nextVersion = (int) $stmt->fetchColumn();

        db()->prepare(
            'INSERT INTO job_versions (job_id, version_number, snapshot_json, changed_by, change_reason)
             VALUES (:jid, :v, :snap, :by, :reason)'
        )->execute([
            ':jid'    => $jobId,
            ':v'      => $nextVersion,
            ':snap'   => json_encode($jobData),
            ':by'     => $changedBy,
            ':reason' => $reason,
        ]);
        return $nextVersion;
    }

    public static function versions(int $jobId): array
    {
        $stmt = db()->prepare(
            'SELECT v.id, v.version_number, v.change_reason, v.created_at,
                    u.first_name, u.last_name, u.email
             FROM job_versions v
             LEFT JOIN users u ON u.id = v.changed_by
             WHERE v.job_id = :jid
             ORDER BY v.version_number DESC'
        );
        $stmt->execute([':jid' => $jobId]);
        return $stmt->fetchAll();
    }

    public static function versionDetail(int $jobId, int $versionNumber): ?array
    {
        $stmt = db()->prepare(
            'SELECT v.*, u.first_name, u.last_name, u.email
             FROM job_versions v
             LEFT JOIN users u ON u.id = v.changed_by
             WHERE v.job_id = :jid AND v.version_number = :vnum
             LIMIT 1'
        );
        $stmt->execute([':jid' => $jobId, ':vnum' => $versionNumber]);
        $row = $stmt->fetch();
        if (!$row) return null;
        $row['snapshot'] = json_decode($row['snapshot_json'], true);
        return $row;
    }
}
