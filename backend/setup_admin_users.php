<?php
// backend/setup_admin_users.php
// Adds admin user management endpoints.
// Run once: C:\xampp\php\php.exe backend\setup_admin_users.php
// Then delete this file.

declare(strict_types=1);

$base = __DIR__;

$files = [

// ---------------------------------------------------------------
'controllers/AdminUserController.php' => <<<'PHP'
<?php
// backend/controllers/AdminUserController.php

declare(strict_types=1);

final class AdminUserController
{
    private const ROLES = ['super_admin', 'recruiter', 'candidate', 'employer', 'vendor'];
    private const STATUSES = ['pending', 'active', 'suspended', 'deactivated'];

    public static function index(): void
    {
        require_role('super_admin');

        $where = ['u.deleted_at IS NULL'];
        $params = [];

        if (!empty($_GET['role'])) {
            $where[] = 'EXISTS (SELECT 1 FROM user_roles ur WHERE ur.user_id = u.id AND ur.role_id = (SELECT id FROM roles WHERE name = :role))';
            $params[':role'] = $_GET['role'];
        }
        if (!empty($_GET['status'])) {
            $where[] = 'u.account_status = :status';
            $params[':status'] = $_GET['status'];
        }
        if (!empty($_GET['q'])) {
            $where[] = '(u.email LIKE :q OR u.first_name LIKE :q OR u.last_name LIKE :q)';
            $params[':q'] = '%' . $_GET['q'] . '%';
        }

        $sql = 'SELECT u.id, u.email, u.first_name, u.last_name, u.phone,
                       u.account_status, u.email_verified_at, u.last_login_at, u.created_at,
                       (SELECT GROUP_CONCAT(r.name) FROM user_roles ur JOIN roles r ON r.id = ur.role_id WHERE ur.user_id = u.id) AS roles_csv
                FROM users u
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY u.created_at DESC
                LIMIT 500';

        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) {
            $r['roles'] = $r['roles_csv'] ? explode(',', $r['roles_csv']) : [];
            unset($r['roles_csv']);
        }
        unset($r);

        json_out(['ok' => true, 'data' => $rows]);
    }

    public static function setRole(int $id): void
    {
        require_role('super_admin');
        $data = read_json();
        $role = trim((string) ($data['role'] ?? ''));

        if (!in_array($role, self::ROLES, true)) {
            json_out(['ok' => false, 'error' => 'Invalid role'], 422);
        }

        $stmt = db()->prepare('SELECT id FROM users WHERE id = :id AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([':id' => $id]);
        if (!$stmt->fetchColumn()) {
            json_out(['ok' => false, 'error' => 'User not found'], 404);
        }

        $pdo = db();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM user_roles WHERE user_id = :uid')->execute([':uid' => $id]);
            $pdo->prepare(
                'INSERT INTO user_roles (user_id, role_id)
                 SELECT :uid, id FROM roles WHERE name = :role'
            )->execute([':uid' => $id, ':role' => $role]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        json_out(['ok' => true]);
    }

    public static function setStatus(int $id): void
    {
        require_role('super_admin');
        $data = read_json();
        $status = trim((string) ($data['status'] ?? ''));

        if (!in_array($status, self::STATUSES, true)) {
            json_out(['ok' => false, 'error' => 'Invalid status'], 422);
        }

        $stmt = db()->prepare('SELECT id FROM users WHERE id = :id AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([':id' => $id]);
        if (!$stmt->fetchColumn()) {
            json_out(['ok' => false, 'error' => 'User not found'], 404);
        }

        db()->prepare('UPDATE users SET account_status = :s WHERE id = :id')
            ->execute([':s' => $status, ':id' => $id]);

        json_out(['ok' => true]);
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
if (strpos($routes, '/admin/users') === false) {
    $injection = <<<'ROUTES'

    // ---- Admin users ----
    ['GET',   '/admin/users',              fn() => AdminUserController::index()],
    ['PATCH', '/admin/users/{id}/role',    fn($id) => AdminUserController::setRole((int) $id)],
    ['PATCH', '/admin/users/{id}/status',  fn($id) => AdminUserController::setStatus((int) $id)],
ROUTES;
    $routes = preg_replace('/\];\s*$/', $injection . "\n];\n", $routes);
    file_put_contents($routesPath, $routes);
    echo "Updated: $routesPath\n";
} else {
    echo "Routes already include /admin/users\n";
}

echo "\nDone.\n";