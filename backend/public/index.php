<?php
// backend/public/index.php
// Front controller — all HTTP requests enter here.

declare(strict_types=1);

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../services/JwtService.php';
require __DIR__ . '/../models/User.php';
require __DIR__ . '/../controllers/AuthController.php';
require __DIR__ . '/../middleware/AuthMiddleware.php';
require __DIR__ . '/../models/Job.php';
require __DIR__ . '/../middleware/PermissionMiddleware.php';
require __DIR__ . '/../controllers/JobController.php';
require __DIR__ . '/../controllers/MyJobsController.php';
require __DIR__ . '/../middleware/RoleMiddleware.php';
require __DIR__ . '/../models/CandidateProfile.php';
require __DIR__ . '/../models/Application.php';
require __DIR__ . '/../controllers/CandidateController.php';
require __DIR__ . '/../controllers/ApplicationController.php';
require __DIR__ . '/../controllers/ReceivedApplicationsController.php';
require __DIR__ . '/../models/Resume.php';
require __DIR__ . '/../controllers/ResumeController.php';
require __DIR__ . '/../models/Skill.php';
require __DIR__ . '/../controllers/SkillController.php';
require __DIR__ . '/../controllers/DashboardController.php';
require __DIR__ . '/../controllers/AdminUserController.php';
require __DIR__ . '/../models/Notification.php';
require __DIR__ . '/../controllers/NotificationController.php';
require __DIR__ . '/../models/SavedJob.php';
require __DIR__ . '/../controllers/SavedJobController.php';
require __DIR__ . '/../models/TalentRequest.php';
require __DIR__ . '/../controllers/TalentRequestController.php';

// ---- CORS ----
$allowedOrigin = env('CORS_ALLOWED_ORIGIN', 'http://localhost:5173');
header("Access-Control-Allow-Origin: $allowedOrigin");
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ---- JSON helpers ----
function json_out(mixed $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function read_json(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === '' || $raw === false) {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

// ---- Load routes ----
$routes = require __DIR__ . '/../routes/api.php';

// ---- Normalize path ----
$method = $_SERVER['REQUEST_METHOD'];
$path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';

// Allow both /api/... and /... by stripping a leading /api
if (str_starts_with($path, '/api')) {
    $path = substr($path, 4) ?: '/';
}
// ---- Dispatch ----
$dispatch = false;

foreach ($routes as [$routeMethod, $routePath, $handler]) {
    if ($routeMethod !== $method) {
        continue;
    }

    // Convert /jobs/{id}/status → #^/jobs/([^/]+)/status$#
    $pattern = preg_replace('#\{[^/]+\}#', '([^/]+)', $routePath);
    $pattern = '#^' . $pattern . '$#';

    if (preg_match($pattern, $path, $matches)) {
        array_shift($matches); // drop the full match, keep captured params
        try {
            $handler(...$matches);
        } catch (Throwable $e) {
            json_out(['ok' => false, 'error' => $e->getMessage()], 500);
        }
        $dispatch = true;
        break;
    }
}

if (!$dispatch) {
    json_out([
        'ok'     => false,
        'error'  => 'Not found',
        'method' => $method,
        'path'   => $path,
    ], 404);
}