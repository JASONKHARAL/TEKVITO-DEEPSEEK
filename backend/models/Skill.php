<?php
// backend/models/Skill.php

declare(strict_types=1);

final class Skill
{
    public static function all(): array
    {
        $stmt = db()->query('SELECT id, name FROM skills ORDER BY name');
        return $stmt->fetchAll();
    }

    public static function findByName(string $name): ?array
    {
        $stmt = db()->prepare('SELECT id, name FROM skills WHERE name = :n LIMIT 1');
        $stmt->execute([':n' => $name]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(string $name): int
    {
        $stmt = db()->prepare('INSERT INTO skills (name) VALUES (:n)');
        $stmt->execute([':n' => $name]);
        return (int) db()->lastInsertId();
    }

    public static function listForCandidate(int $candidateId): array
    {
        $stmt = db()->prepare(
            'SELECT s.id, s.name, cs.proficiency, cs.years_experience
             FROM candidate_skills cs
             JOIN skills s ON s.id = cs.skill_id
             WHERE cs.candidate_id = :cid
             ORDER BY s.name'
        );
        $stmt->execute([':cid' => $candidateId]);
        return $stmt->fetchAll();
    }

    public static function addForCandidate(int $candidateId, int $skillId, ?string $proficiency, ?float $years): void
    {
        $stmt = db()->prepare(
            'INSERT INTO candidate_skills (candidate_id, skill_id, proficiency, years_experience)
             VALUES (:cid, :sid, :prof, :yrs)
             ON DUPLICATE KEY UPDATE proficiency = VALUES(proficiency), years_experience = VALUES(years_experience)'
        );
        $stmt->execute([
            ':cid'  => $candidateId,
            ':sid'  => $skillId,
            ':prof' => $proficiency,
            ':yrs'  => $years,
        ]);
    }

    public static function removeForCandidate(int $candidateId, int $skillId): bool
    {
        $stmt = db()->prepare(
            'DELETE FROM candidate_skills WHERE candidate_id = :cid AND skill_id = :sid'
        );
        $stmt->execute([':cid' => $candidateId, ':sid' => $skillId]);
        return $stmt->rowCount() > 0;
    }
}