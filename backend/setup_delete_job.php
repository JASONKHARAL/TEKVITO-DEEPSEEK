<?php
// backend/setup_delete_job.php
declare(strict_types=1);

$base = __DIR__;

// 1. Add destroy method to JobController
$ctrlPath = $base . '/controllers/JobController.php';
$ctrl = file_get_contents($ctrlPath);

if (strpos($ctrl, 'public static function destroy(') === false) {
    $newMethod = <<<'PHPCODE'

    public static function destroy(int $id): void
    {
        $payload = require_auth();
        $userId = (int) $payload['sub'];
        $roles = $payload['roles'] ?? [];
        $isAdmin = in_array('super_admin', $roles, true) || in_array('admin', $roles, true);

        $job = Job::findById($id);
        if (!$job) {
            json_out(['ok' => false, 'error' => 'Job not found'], 404);
        }
        
        // Only owner or super_admin can delete
        if (!$isAdmin && (int) $job['created_by'] !== $userId) {
            json_out(['ok' => false, 'error' => 'Unauthorized to delete this job'], 403);
        }

        // We do a hard delete here. If you want a soft delete, you'd update status to 'deleted' instead.
        $stmt = db()->prepare('DELETE FROM jobs WHERE id = :id');
        $stmt->execute([':id' => $id]);

        json_out(['ok' => true, 'message' => 'Job deleted successfully']);
    }
PHPCODE;
    // Insert before the final closing brace
    $ctrl = preg_replace('/}\s*$/', $newMethod . "\n}\n", $ctrl);
    file_put_contents($ctrlPath, $ctrl);
    echo "Updated JobController.php\n";
}

// 2. Add route
$routesPath = $base . '/routes/api.php';
$routes = file_get_contents($routesPath);
if (strpos($routes, "['DELETE', '/jobs/{id}'") === false) {
    $injection = <<<'ROUTES'
    ['DELETE', '/jobs/{id}',      fn($id) => JobController::destroy((int) $id)],
ROUTES;
    $routes = preg_replace('/\];\s*$/', $injection . "\n];\n", $routes);
    file_put_contents($routesPath, $routes);
    echo "Updated api.php\n";
}

echo "Done.\n";