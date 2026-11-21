<?php
// backend/setup_saved_jobs.php
// Saved jobs endpoints.
// Run once: C:\xampp\php\php.exe backend\setup_saved_jobs.php
// Then delete this file.

declare(strict_types=1);

$base = __DIR__;

$files = [

'models/SavedJob.php' => <<<'PHP'
<?php
// backend/models/SavedJob.php

declare(strict_types=1);

final class SavedJob
{
    public static function save(int $userId, int $jobId): bool
    {
        $stmt = db()->prepare(
            'INSERT IGNORE INTO saved_jobs (user_id, job_id) VALUES (:uid, :jid)'
        );
        $stmt->execute([':uid' => $userId, ':jid' => $jobId]);
        return $stmt->rowCount() > 0;
    }

    public static function unsave(int $userId, int $jobId): bool
    {
        $stmt = db()->prepare('DELETE FROM saved_jobs WHERE user_id = :uid AND job_id = :jid');
        $stmt->execute([':uid' => $userId, ':jid' => $jobId]);
        return $stmt->rowCount() > 0;
    }

    public static function isSaved(int $userId, int $jobId): bool
    {
        $stmt = db()->prepare('SELECT 1 FROM saved_jobs WHERE user_id = :uid AND job_id = :jid LIMIT 1');
        $stmt->execute([':uid' => $userId, ':jid' => $jobId]);
        return (bool) $stmt->fetchColumn();
    }

    public static function listFor(int $userId): array
    {
        $stmt = db()->prepare(
            'SELECT j.id, j.title, j.slug, j.employment_type, j.workplace_type,
                    j.location, j.country, j.salary_min, j.salary_max, j.salary_currency,
                    j.status, j.published_at, j.created_at,
                    c.name AS category_name,
                    sj.created_at AS saved_at
             FROM saved_jobs sj
             JOIN jobs j ON j.id = sj.job_id
             LEFT JOIN job_categories c ON c.id = j.category_id
             WHERE sj.user_id = :uid
             ORDER BY sj.created_at DESC'
        );
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll();
    }
}
PHP,

'controllers/SavedJobController.php' => <<<'PHP'
<?php
// backend/controllers/SavedJobController.php

declare(strict_types=1);

final class SavedJobController
{
    public static function index(): void
    {
        $payload = require_role('candidate');
        json_out(['ok' => true, 'data' => SavedJob::listFor((int) $payload['sub'])]);
    }

    public static function save(int $jobId): void
    {
        $payload = require_role('candidate');
        $job = Job::findById($jobId);
        if (!$job) {
            json_out(['ok' => false, 'error' => 'Job not found'], 404);
        }
        SavedJob::save((int) $payload['sub'], $jobId);
        json_out(['ok' => true, 'saved' => true]);
    }

    public static function unsave(int $jobId): void
    {
        $payload = require_role('candidate');
        SavedJob::unsave((int) $payload['sub'], $jobId);
        json_out(['ok' => true, 'saved' => false]);
    }

    public static function isSaved(int $jobId): void
    {
        $payload = require_auth();
        json_out(['ok' => true, 'saved' => SavedJob::isSaved((int) $payload['sub'], $jobId)]);
    }
}
PHP,

];

foreach ($files as $rel => $content) {
    $fullPath = $base . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $dir = dirname($fullPath);
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    file_put_contents($fullPath, $content);
    echo "Wrote: $fullPath\n";
}

$routesPath = $base . '/routes/api.php';
$routes = file_get_contents($routesPath);
if (strpos($routes, '/saved-jobs') === false) {
    $injection = <<<'ROUTES'

    // ---- Saved jobs ----
    ['GET',    '/saved-jobs',            fn() => SavedJobController::index()],
    ['POST',   '/jobs/{id}/save',        fn($id) => SavedJobController::save((int) $id)],
    ['DELETE', '/jobs/{id}/save',        fn($id) => SavedJobController::unsave((int) $id)],
    ['GET',    '/jobs/{id}/is-saved',    fn($id) => SavedJobController::isSaved((int) $id)],
ROUTES;
    $routes = preg_replace('/\];\s*$/', $injection . "\n];\n", $routes);
    file_put_contents($routesPath, $routes);
    echo "Updated: $routesPath\n";
} else {
    echo "Routes already include /saved-jobs\n";
}

echo "\nDone.\n";