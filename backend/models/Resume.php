<?php
// backend/models/Resume.php

declare(strict_types=1);

final class Resume
{
    public static function listFor(int $candidateId): array
    {
        $stmt = db()->prepare(
            'SELECT id, original_filename, file_type, file_size_bytes, is_primary, uploaded_at
             FROM candidate_resumes
             WHERE candidate_id = :cid
             ORDER BY is_primary DESC, uploaded_at DESC'
        );
        $stmt->execute([':cid' => $candidateId]);
        return $stmt->fetchAll();
    }

    public static function create(int $candidateId, array $file): int
    {
        $isPrimary = !self::hasPrimary($candidateId);
        $stmt = db()->prepare(
            'INSERT INTO candidate_resumes
             (candidate_id, original_filename, stored_filename, file_path, file_type, file_size_bytes, is_primary)
             VALUES (:cid, :orig, :stored, :path, :type, :size, :primary)'
        );
        $stmt->execute([
            ':cid'     => $candidateId,
            ':orig'    => $file['original_filename'],
            ':stored'  => $file['stored_filename'],
            ':path'    => $file['file_path'],
            ':type'    => $file['file_type'],
            ':size'    => $file['file_size_bytes'],
            ':primary' => $isPrimary ? 1 : 0,
        ]);
        return (int) db()->lastInsertId();
    }

    public static function find(int $id, int $candidateId): ?array
    {
        $stmt = db()->prepare(
            'SELECT * FROM candidate_resumes WHERE id = :id AND candidate_id = :cid LIMIT 1'
        );
        $stmt->execute([':id' => $id, ':cid' => $candidateId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function delete(int $id, int $candidateId): bool
    {
        $resume = self::find($id, $candidateId);
        if (!$resume) return false;

        if (is_file($resume['file_path'])) {
            @unlink($resume['file_path']);
        }
        db()->prepare('DELETE FROM candidate_resumes WHERE id = :id')->execute([':id' => $id]);

        // If we deleted the primary, promote the next one
        if ($resume['is_primary']) {
            $next = db()->prepare(
                'SELECT id FROM candidate_resumes WHERE candidate_id = :cid ORDER BY uploaded_at DESC LIMIT 1'
            );
            $next->execute([':cid' => $candidateId]);
            $nextId = $next->fetchColumn();
            if ($nextId) {
                db()->prepare('UPDATE candidate_resumes SET is_primary = 1 WHERE id = :id')
                    ->execute([':id' => $nextId]);
            }
        }
        return true;
    }

    public static function makePrimary(int $id, int $candidateId): bool
    {
        $resume = self::find($id, $candidateId);
        if (!$resume) return false;

        $pdo = db();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE candidate_resumes SET is_primary = 0 WHERE candidate_id = :cid')
                ->execute([':cid' => $candidateId]);
            $pdo->prepare('UPDATE candidate_resumes SET is_primary = 1 WHERE id = :id')
                ->execute([':id' => $id]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        return true;
    }

    private static function hasPrimary(int $candidateId): bool
    {
        $stmt = db()->prepare(
            'SELECT 1 FROM candidate_resumes WHERE candidate_id = :cid AND is_primary = 1 LIMIT 1'
        );
        $stmt->execute([':cid' => $candidateId]);
        return (bool) $stmt->fetchColumn();
    }
}