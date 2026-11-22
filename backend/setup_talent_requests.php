<?php
// backend/setup_talent_requests.php
// Talent request endpoints (public submit, admin manage).
// Run once: C:\xampp\php\php.exe backend\setup_talent_requests.php
// Then delete this file.

declare(strict_types=1);

$base = __DIR__;

$files = [

'models/TalentRequest.php' => <<<'PHP'
<?php
// backend/models/TalentRequest.php

declare(strict_types=1);

final class TalentRequest
{
    public static function create(array $data): int
    {
        $stmt = db()->prepare(
            'INSERT INTO talent_requests
             (contact_name, contact_email, contact_phone, company_name, job_title, job_description,
              required_skills, location, country, budget, timeline, preferred_contact)
             VALUES
             (:name, :email, :phone, :company, :title, :desc,
              :skills, :loc, :country, :budget, :timeline, :contact)'
        );
        $stmt->execute([
            ':name'      => $data['contact_name'],
            ':email'     => $data['contact_email'],
            ':phone'     => $data['contact_phone'] ?? null,
            ':company'   => $data['company_name'],
            ':title'     => $data['job_title'],
            ':desc'      => $data['job_description'],
            ':skills'    => $data['required_skills'] ?? null,
            ':loc'       => $data['location'] ?? null,
            ':country'   => $data['country'] ?? null,
            ':budget'    => $data['budget'] ?? null,
            ':timeline'  => $data['timeline'] ?? 'flexible',
            ':contact'   => $data['preferred_contact'] ?? 'email',
        ]);
        return (int) db()->lastInsertId();
    }

    public static function all(array $filters = []): array
    {
        $where = [];
        $params = [];
        if (!empty($filters['status'])) {
            $where[] = 'tr.status = :status';
            $params[':status'] = $filters['status'];
        }
        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $stmt = db()->prepare(
            "SELECT tr.*,
                    u.first_name AS reviewer_first, u.last_name AS reviewer_last
             FROM talent_requests tr
             LEFT JOIN users u ON u.id = tr.reviewed_by
             $whereSql
             ORDER BY tr.created_at DESC
             LIMIT 500"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = db()->prepare(
            'SELECT tr.*,
                    u.first_name AS reviewer_first, u.last_name AS reviewer_last,
                    j.title AS created_job_title, j.slug AS created_job_slug
             FROM talent_requests tr
             LEFT JOIN users u ON u.id = tr.reviewed_by
             LEFT JOIN jobs j ON j.id = tr.created_job_id
             WHERE tr.id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function updateStatus(int $id, string $status, ?string $notes, int $reviewedBy): bool
    {
        $allowed = ['new', 'in_progress', 'posted', 'closed'];
        if (!in_array($status, $allowed, true)) {
            throw new InvalidArgumentException('Invalid status');
        }

        $stmt = db()->prepare(
            'UPDATE talent_requests
             SET status = :s, admin_notes = COALESCE(:notes, admin_notes),
                 reviewed_by = :by, reviewed_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            ':s'     => $status,
            ':notes' => $notes,
            ':by'    => $reviewedBy,
            ':id'    => $id,
        ]);
        return $stmt->rowCount() > 0;
    }

    public static function linkJob(int $id, int $jobId): void
    {
        db()->prepare(
            'UPDATE talent_requests SET created_job_id = :jid, status = "posted", reviewed_at = NOW() WHERE id = :id'
        )->execute([':jid' => $jobId, ':id' => $id]);
    }
}
PHP,

'controllers/TalentRequestController.php' => <<<'PHP'
<?php
// backend/controllers/TalentRequestController.php

declare(strict_types=1);

final class TalentRequestController
{
    private const TIMELINES = ['asap', '1_2_weeks', '1_month', 'flexible'];
    private const CONTACTS = ['email', 'phone', 'either'];

    /** Public — anyone can submit a talent request. */
    public static function submit(): void
    {
        $data = read_json();

        $required = ['contact_name', 'contact_email', 'company_name', 'job_title', 'job_description'];
        foreach ($required as $field) {
            if (trim((string) ($data[$field] ?? '')) === '') {
                json_out(['ok' => false, 'error' => "Field '$field' is required"], 422);
            }
        }

        if (!filter_var($data['contact_email'], FILTER_VALIDATE_EMAIL)) {
            json_out(['ok' => false, 'error' => 'Invalid email address'], 422);
        }

        $timeline = $data['timeline'] ?? 'flexible';
        if (!in_array($timeline, self::TIMELINES, true)) {
            $timeline = 'flexible';
        }
        $contact = $data['preferred_contact'] ?? 'email';
        if (!in_array($contact, self::CONTACTS, true)) {
            $contact = 'email';
        }

        $data['timeline'] = $timeline;
        $data['preferred_contact'] = $contact;

        $id = TalentRequest::create($data);

        // Notify all super admins
        $admins = db()->query(
            "SELECT u.id FROM users u
             JOIN user_roles ur ON ur.user_id = u.id
             JOIN roles r ON r.id = ur.role_id
             WHERE r.name = 'super_admin'"
        )->fetchAll(PDO::FETCH_COLUMN);

        foreach ($admins as $adminId) {
            Notification::create(
                (int) $adminId,
                'talent_request',
                'New talent request',
                "{$data['company_name']} wants help hiring: {$data['job_title']}",
                '/admin/talent-requests/' . $id
            );
        }

        json_out(['ok' => true, 'id' => $id, 'message' => "Thanks! We'll be in touch within 1 business day."], 201);
    }

    /** Admin — list all requests. */
    public static function index(): void
    {
        require_role('super_admin');
        $filters = ['status' => $_GET['status'] ?? null];
        json_out(['ok' => true, 'data' => TalentRequest::all($filters)]);
    }

    /** Admin — single request detail. */
    public static function show(int $id): void
    {
        require_role('super_admin');
        $req = TalentRequest::find($id);
        if (!$req) {
            json_out(['ok' => false, 'error' => 'Talent request not found'], 404);
        }
        json_out(['ok' => true, 'request' => $req]);
    }

    /** Admin — update status. */
    public static function updateStatus(int $id): void
    {
        $payload = require_role('super_admin');
        $data = read_json();
        $status = trim((string) ($data['status'] ?? ''));
        $notes = isset($data['admin_notes']) ? trim((string) $data['admin_notes']) : null;

        if ($status === '') {
            json_out(['ok' => false, 'error' => 'Status is required'], 422);
        }

        try {
            $ok = TalentRequest::updateStatus($id, $status, $notes, (int) $payload['sub']);
        } catch (InvalidArgumentException $e) {
            json_out(['ok' => false, 'error' => $e->getMessage()], 422);
        }

        if (!$ok) {
            json_out(['ok' => false, 'error' => 'Not found or no change'], 404);
        }

        $updated = TalentRequest::find($id);
        json_out(['ok' => true, 'request' => $updated]);
    }

    /** Admin — link a created job to this request. */
    public static function linkJob(int $id): void
    {
        require_role('super_admin');
        $data = read_json();
        $jobId = isset($data['job_id']) ? (int) $data['job_id'] : 0;

        if (!$jobId) {
            json_out(['ok' => false, 'error' => 'job_id is required'], 422);
        }

        $job = Job::findById($jobId);
        if (!$job) {
            json_out(['ok' => false, 'error' => 'Job not found'], 404);
        }

        TalentRequest::linkJob($id, $jobId);
        $req = TalentRequest::find($id);
        json_out(['ok' => true, 'request' => $req]);
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
if (strpos($routes, '/hire-request') === false) {
    $injection = <<<'ROUTES'

    // ---- Talent requests ----
    ['POST', '/hire-request',                  fn() => TalentRequestController::submit()],
    ['GET',  '/admin/talent-requests',         fn() => TalentRequestController::index()],
    ['GET',  '/admin/talent-requests/{id}',    fn($id) => TalentRequestController::show((int) $id)],
    ['PATCH','/admin/talent-requests/{id}/status', fn($id) => TalentRequestController::updateStatus((int) $id)],
    ['POST', '/admin/talent-requests/{id}/link-job', fn($id) => TalentRequestController::linkJob((int) $id)],
ROUTES;
    $routes = preg_replace('/\];\s*$/', $injection . "\n];\n", $routes);
    file_put_contents($routesPath, $routes);
    echo "Updated: $routesPath\n";
} else {
    echo "Routes already include /hire-request\n";
}

echo "\nDone.\n";