<?php
// backend/controllers/CandidateController.php

declare(strict_types=1);

final class CandidateController
{
    public static function getProfile(): void
    {
        $payload = require_auth();
        $profile = CandidateProfile::getOrCreate((int) $payload['sub']);
        json_out(['ok' => true, 'profile' => $profile]);
    }

    public static function updateProfile(): void
    {
        $payload = require_auth();
        $data = read_json();
        $profile = CandidateProfile::update((int) $payload['sub'], $data);
        json_out(['ok' => true, 'profile' => $profile]);
    }
}