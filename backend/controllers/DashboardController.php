<?php
// backend/controllers/DashboardController.php

declare(strict_types=1);

final class DashboardController
{
    public static function stats(): void
    {
        $payload = require_auth();
        $userId = (int) $payload['sub'];
        $roles = $payload['roles'] ?? [];

        $isCandidate = in_array('candidate', $roles, true);
        $isAdmin = in_array('super_admin', $roles, true) || in_array('recruiter', $roles, true);

        if ($isCandidate) {
            json_out(['ok' => true, 'view' => 'candidate'] + self::candidateStats($userId));
        }
        if ($isAdmin) {
            json_out(['ok' => true, 'view' => 'admin'] + self::adminStats($userId, in_array('super_admin', $roles, true)));
        }

        json_out(['ok' => false, 'error' => 'No dashboard available for your role'], 403);
    }

    private static function candidateStats(int $userId): array
    {
        $profile = CandidateProfile::getOrCreate($userId);
        $cid = (int) $profile['id'];

        $totalApps = (int) db()->query(
            "SELECT COUNT(*) FROM applications WHERE candidate_id = " . $cid
        )->fetchColumn();

        $byStatus = [];
        $stmt = db()->prepare(
            'SELECT status, COUNT(*) AS n FROM applications WHERE candidate_id = :cid GROUP BY status'
        );
        $stmt->execute([':cid' => $cid]);
        foreach ($stmt->fetchAll() as $r) {
            $byStatus[$r['status']] = (int) $r['n'];
        }

        $resumeCount = (int) db()->query(
            "SELECT COUNT(*) FROM candidate_resumes WHERE candidate_id = " . $cid
        )->fetchColumn();

        $skillCount = (int) db()->query(
            "SELECT COUNT(*) FROM candidate_skills WHERE candidate_id = " . $cid
        )->fetchColumn();

        return [
            'stats' => [
                'applications'   => $totalApps,
                'resumes'        => $resumeCount,
                'skills'         => $skillCount,
                'by_status'      => $byStatus,
            ],
        ];
    }

    private static function adminStats(int $userId, bool $isSuperAdmin): array
    {
        $pdo = db();

        $whereJobs = $isSuperAdmin ? '' : " WHERE created_by = $userId";

        $totalUsers = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE deleted_at IS NULL")->fetchColumn();
        $totalJobs = (int) $pdo->query("SELECT COUNT(*) FROM jobs $whereJobs")->fetchColumn();

        $appsSql = $isSuperAdmin
            ? 'SELECT COUNT(*) FROM applications'
            : "SELECT COUNT(*) FROM applications a JOIN jobs j ON j.id = a.job_id WHERE j.created_by = $userId";
        $totalApps = (int) $pdo->query($appsSql)->fetchColumn();

        $hiresSql = $isSuperAdmin
            ? "SELECT COUNT(*) FROM applications WHERE status = 'hired'"
            : "SELECT COUNT(*) FROM applications a JOIN jobs j ON j.id = a.job_id WHERE j.created_by = $userId AND a.status = 'hired'";
        $totalHires = (int) $pdo->query($hiresSql)->fetchColumn();

        $jobsByStatus = [];
        $jobsStatusSql = $isSuperAdmin
            ? 'SELECT status, COUNT(*) AS n FROM jobs GROUP BY status'
            : "SELECT status, COUNT(*) AS n FROM jobs WHERE created_by = $userId GROUP BY status";
        foreach ($pdo->query($jobsStatusSql)->fetchAll() as $r) {
            $jobsByStatus[$r['status']] = (int) $r['n'];
        }

        $appsByStatus = [];
        $appsStatusSql = $isSuperAdmin
            ? 'SELECT status, COUNT(*) AS n FROM applications GROUP BY status'
            : "SELECT a.status, COUNT(*) AS n FROM applications a JOIN jobs j ON j.id = a.job_id WHERE j.created_by = $userId GROUP BY a.status";
        foreach ($pdo->query($appsStatusSql)->fetchAll() as $r) {
            $appsByStatus[$r['status']] = (int) $r['n'];
        }

        $recentSql = $isSuperAdmin
            ? 'SELECT a.id, a.status, a.applied_at,
                      j.title AS job_title, j.id AS job_id,
                      u.first_name, u.last_name
               FROM applications a
               JOIN jobs j ON j.id = a.job_id
               JOIN candidate_profiles cp ON cp.id = a.candidate_id
               JOIN users u ON u.id = cp.user_id
               ORDER BY a.applied_at DESC
               LIMIT 5'
            : "SELECT a.id, a.status, a.applied_at,
                      j.title AS job_title, j.id AS job_id,
                      u.first_name, u.last_name
               FROM applications a
               JOIN jobs j ON j.id = a.job_id
               JOIN candidate_profiles cp ON cp.id = a.candidate_id
               JOIN users u ON u.id = cp.user_id
               WHERE j.created_by = $userId
               ORDER BY a.applied_at DESC
               LIMIT 5";
        $recentApps = $pdo->query($recentSql)->fetchAll();

        $topJobsSql = $isSuperAdmin
            ? 'SELECT j.id, j.title, COUNT(a.id) AS app_count
               FROM jobs j
               LEFT JOIN applications a ON a.job_id = j.id
               GROUP BY j.id, j.title
               HAVING app_count > 0
               ORDER BY app_count DESC
               LIMIT 5'
            : "SELECT j.id, j.title, COUNT(a.id) AS app_count
               FROM jobs j
               LEFT JOIN applications a ON a.job_id = j.id
               WHERE j.created_by = $userId
               GROUP BY j.id, j.title
               HAVING app_count > 0
               ORDER BY app_count DESC
               LIMIT 5";
        $topJobs = $pdo->query($topJobsSql)->fetchAll();

        return [
            'stats' => [
                'users'          => $totalUsers,
                'jobs'           => $totalJobs,
                'applications'   => $totalApps,
                'hires'          => $totalHires,
                'jobs_by_status' => $jobsByStatus,
                'apps_by_status' => $appsByStatus,
            ],
            'recent_applications' => $recentApps,
            'top_jobs'            => $topJobs,
        ];
    }
}