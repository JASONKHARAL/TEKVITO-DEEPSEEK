<?php
// backend/controllers/SkillController.php

declare(strict_types=1);

final class SkillController
{
    private const PROF_LEVELS = ['beginner', 'intermediate', 'advanced', 'expert'];

    public static function index(): void
    {
        json_out(['ok' => true, 'skills' => Skill::all()]);
    }

    public static function mySkills(): void
    {
        $payload = require_role('candidate');
        $profile = CandidateProfile::getOrCreate((int) $payload['sub']);
        json_out(['ok' => true, 'skills' => Skill::listForCandidate((int) $profile['id'])]);
    }

    public static function add(): void
    {
        $payload = require_role('candidate');
        $profile = CandidateProfile::getOrCreate((int) $payload['sub']);

        $data = read_json();
        $skillId = isset($data['skill_id']) ? (int) $data['skill_id'] : 0;
        $proficiency = isset($data['proficiency']) ? (string) $data['proficiency'] : null;
        $years = isset($data['years_experience']) && $data['years_experience'] !== ''
            ? (float) $data['years_experience'] : null;

        if (!$skillId) {
            json_out(['ok' => false, 'error' => 'skill_id is required'], 422);
        }
        if ($proficiency !== null && !in_array($proficiency, self::PROF_LEVELS, true)) {
            json_out(['ok' => false, 'error' => 'Invalid proficiency level'], 422);
        }

        // Verify skill exists
        $stmt = db()->prepare('SELECT id FROM skills WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $skillId]);
        if (!$stmt->fetchColumn()) {
            json_out(['ok' => false, 'error' => 'Skill not found'], 404);
        }

        Skill::addForCandidate((int) $profile['id'], $skillId, $proficiency, $years);
        json_out(['ok' => true, 'skills' => Skill::listForCandidate((int) $profile['id'])]);
    }

    public static function remove(int $skillId): void
    {
        $payload = require_role('candidate');
        $profile = CandidateProfile::getOrCreate((int) $payload['sub']);
        if (!Skill::removeForCandidate((int) $profile['id'], $skillId)) {
            json_out(['ok' => false, 'error' => 'Skill not found on profile'], 404);
        }
        json_out(['ok' => true, 'skills' => Skill::listForCandidate((int) $profile['id'])]);
    }

    public static function create(): void
    {
        require_permission('settings.manage');
        $data = read_json();
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            json_out(['ok' => false, 'error' => 'Name is required'], 422);
        }
        if (strlen($name) > 150) {
            json_out(['ok' => false, 'error' => 'Name too long'], 422);
        }
        if (Skill::findByName($name)) {
            json_out(['ok' => false, 'error' => 'Skill already exists'], 409);
        }
        $id = Skill::create($name);
        json_out(['ok' => true, 'skill' => ['id' => $id, 'name' => $name]], 201);
    }
}