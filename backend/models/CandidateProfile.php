<?php
// backend/models/CandidateProfile.php

declare(strict_types=1);

final class CandidateProfile
{
    public static function getOrCreate(int $userId): array
    {
        $stmt = db()->prepare('SELECT * FROM candidate_profiles WHERE user_id = :uid LIMIT 1');
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch();
        if ($row) {
            return $row;
        }
        db()->prepare(
            'INSERT INTO candidate_profiles (user_id) VALUES (:uid)'
        )->execute([':uid' => $userId]);
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetch();
    }

    public static function update(int $userId, array $data): array
    {
        $profile = self::getOrCreate($userId);
        $allowed = ['headline','professional_summary','location','country',
                    'years_experience','employment_status','linkedin_url','portfolio_url'];
        $set = [];
        $params = [':id' => $profile['id']];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $data)) {
                $set[] = "$f = :$f";
                $params[":$f"] = $data[$f];
            }
        }
        if (!$set) {
            return $profile;
        }
        $sql = 'UPDATE candidate_profiles SET ' . implode(', ', $set) . ' WHERE id = :id';
        db()->prepare($sql)->execute($params);
        $stmt = db()->prepare('SELECT * FROM candidate_profiles WHERE id = :id');
        $stmt->execute([':id' => $profile['id']]);
        return $stmt->fetch();
    }
}