<?php
// backend/controllers/ResumeController.php

declare(strict_types=1);

final class ResumeController
{
    private const MAX_SIZE = 5 * 1024 * 1024; // 5 MB
    private const ALLOWED_TYPES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];
    private const ALLOWED_EXT = ['pdf', 'doc', 'docx'];

    public static function index(): void
    {
        $payload = require_role('candidate');
        $profile = CandidateProfile::getOrCreate((int) $payload['sub']);
        json_out(['ok' => true, 'data' => Resume::listFor((int) $profile['id'])]);
    }

    public static function upload(): void
    {
        $payload = require_role('candidate');
        $profile = CandidateProfile::getOrCreate((int) $payload['sub']);

        if (empty($_FILES['resume'])) {
            json_out(['ok' => false, 'error' => 'No file uploaded'], 422);
        }
        $f = $_FILES['resume'];

        if ($f['error'] !== UPLOAD_ERR_OK) {
            json_out(['ok' => false, 'error' => 'Upload error code: ' . $f['error']], 422);
        }
        if ($f['size'] > self::MAX_SIZE) {
            json_out(['ok' => false, 'error' => 'File too large (max 5MB)'], 422);
        }

        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXT, true)) {
            json_out(['ok' => false, 'error' => 'Only PDF or DOCX allowed'], 422);
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $f['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mime, self::ALLOWED_TYPES, true)) {
            json_out(['ok' => false, 'error' => 'Invalid file type'], 422);
        }

        $dir = __DIR__ . '/../storage/uploads/resumes';
        if (!is_dir($dir)) mkdir($dir, 0777, true);

        $storedName = 'resume_' . $profile['id'] . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $dest = $dir . '/' . $storedName;

        if (!move_uploaded_file($f['tmp_name'], $dest)) {
            json_out(['ok' => false, 'error' => 'Failed to save file'], 500);
        }

        $id = Resume::create((int) $profile['id'], [
            'original_filename' => $f['name'],
            'stored_filename'   => $storedName,
            'file_path'         => $dest,
            'file_type'         => $mime,
            'file_size_bytes'   => $f['size'],
        ]);

        $resume = Resume::find($id, (int) $profile['id']);
        json_out(['ok' => true, 'resume' => $resume], 201);
    }

    public static function download(int $id): void
    {
        $payload = require_auth();
        $userId = (int) $payload['sub'];
        $roles  = $payload['roles'] ?? [];
        $isAdmin = in_array('super_admin', $roles, true) || in_array('recruiter', $roles, true);

        if ($isAdmin) {
            // Admin/recruiter: can download any resume by ID
            $stmt = db()->prepare('SELECT * FROM candidate_resumes WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $id]);
            $resume = $stmt->fetch();
        } else {
            // Candidate: only their own
            $profile = CandidateProfile::getOrCreate($userId);
            $resume  = Resume::find($id, (int) $profile['id']);
        }

        if (!$resume || !is_file($resume['file_path'])) {
            json_out(['ok' => false, 'error' => 'File not found'], 404);
        }

        header('Content-Type: ' . $resume['file_type']);
        header('Content-Disposition: attachment; filename="' . addslashes($resume['original_filename']) . '"');
        header('Content-Length: ' . filesize($resume['file_path']));
        readfile($resume['file_path']);
        exit;
    }

    public static function delete(int $id): void
    {
        $payload = require_role('candidate');
        $profile = CandidateProfile::getOrCreate((int) $payload['sub']);
        if (!Resume::delete($id, (int) $profile['id'])) {
            json_out(['ok' => false, 'error' => 'Not found'], 404);
        }
        json_out(['ok' => true]);
    }

    public static function makePrimary(int $id): void
    {
        $payload = require_role('candidate');
        $profile = CandidateProfile::getOrCreate((int) $payload['sub']);
        if (!Resume::makePrimary($id, (int) $profile['id'])) {
            json_out(['ok' => false, 'error' => 'Not found'], 404);
        }
        json_out(['ok' => true]);
    }
}