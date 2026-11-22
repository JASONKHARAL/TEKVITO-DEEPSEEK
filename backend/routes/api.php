<?php
// backend/routes/api.php

declare(strict_types=1);

return [
    ['GET', '/ping', fn() => json_out(['ok' => true, 'time' => date('c')])],

    ['GET', '/db-check', function () {
        try {
            $row = db()->query('SELECT 1 AS one')->fetch();
            json_out(['ok' => true, 'db' => 'connected', 'result' => $row]);
        } catch (Throwable $e) {
            json_out(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }],

    // ---- Auth ----
    ['POST', '/auth/login',    fn() => AuthController::login()],
    ['POST', '/auth/register', fn() => AuthController::register()],
    ['GET',  '/auth/me',       fn() => AuthController::me()],

    // ---- Jobs ----
    ['GET',  '/jobs',            fn() => JobController::index()],
    ['GET',  '/jobs/categories', fn() => JobController::categories()],
    ['POST', '/jobs',            fn() => JobController::store()],
    ['GET',  '/jobs/{id}',       fn($id) => JobController::show((int) $id)],
    ['PATCH','/jobs/{id}/status', fn($id) => JobController::updateStatus((int) $id)],

    // ---- My Jobs ----
    ['GET', '/my/jobs',       fn() => MyJobsController::index()],
    ['GET', '/my/jobs/{id}',  fn($id) => MyJobsController::show((int) $id)],

    // ---- Candidate ----
    ['GET', '/candidate/profile',  fn() => CandidateController::getProfile()],
    ['PUT', '/candidate/profile',  fn() => CandidateController::updateProfile()],

    // ---- Applications ----
    ['POST', '/jobs/{id}/apply',        fn($id) => ApplicationController::apply((int) $id)],
    ['GET',  '/jobs/{id}/has-applied',  fn($id) => ApplicationController::hasApplied((int) $id)],
    ['GET',  '/applications',           fn() => ApplicationController::index()],
    ['GET',  '/applications/{id}',      fn($id) => ApplicationController::show((int) $id)],

    // ---- Received Applications (recruiter/admin) ----
    ['GET',  '/received-applications',           fn() => ReceivedApplicationsController::index()],
    ['GET',  '/received-applications/{id}',      fn($id) => ReceivedApplicationsController::show((int) $id)],
    ['PATCH','/received-applications/{id}/status', fn($id) => ReceivedApplicationsController::updateStatus((int) $id)],

    // ---- Resumes (candidate) ----
    ['GET',    '/resumes',              fn() => ResumeController::index()],
    ['POST',   '/resumes',              fn() => ResumeController::upload()],
    ['GET',    '/resumes/{id}/download', fn($id) => ResumeController::download((int) $id)],
    ['DELETE', '/resumes/{id}',         fn($id) => ResumeController::delete((int) $id)],
    ['PATCH',  '/resumes/{id}/primary', fn($id) => ResumeController::makePrimary((int) $id)],

    ['POST', '/applications/{id}/withdraw', fn($id) => ApplicationController::withdraw((int) $id)],

    // ---- Job edit + versions ----
    ['PUT', '/jobs/{id}',                    fn($id) => JobController::update((int) $id)],
    ['GET', '/jobs/{id}/versions',           fn($id) => JobController::versions((int) $id)],
    ['GET', '/jobs/{id}/versions/{vnum}',    fn($id, $vnum) => JobController::versionDetail((int) $id, (int) $vnum)],

    // ---- Skills ----
    ['GET',    '/skills',                       fn() => SkillController::index()],
    ['POST',   '/skills',                       fn() => SkillController::create()],
    ['GET',    '/candidate/skills',             fn() => SkillController::mySkills()],
    ['POST',   '/candidate/skills',             fn() => SkillController::add()],
    ['DELETE', '/candidate/skills/{skillId}',   fn($sid) => SkillController::remove((int) $sid)],

    ['GET', '/dashboard/stats', fn() => DashboardController::stats()],

    // ---- Admin users ----
    ['GET',   '/admin/users',              fn() => AdminUserController::index()],
    ['PATCH', '/admin/users/{id}/role',    fn($id) => AdminUserController::setRole((int) $id)],
    ['PATCH', '/admin/users/{id}/status',  fn($id) => AdminUserController::setStatus((int) $id)],

    // ---- Notifications ----
    ['GET',   '/notifications',                  fn() => NotificationController::index()],
    ['GET',   '/notifications/unread-count',     fn() => NotificationController::unreadCount()],
    ['PATCH', '/notifications/{id}/read',        fn($id) => NotificationController::markRead((int) $id)],
    ['PATCH', '/notifications/read-all',         fn() => NotificationController::markAllRead()],

    // ---- Saved jobs ----
    ['GET',    '/saved-jobs',            fn() => SavedJobController::index()],
    ['POST',   '/jobs/{id}/save',        fn($id) => SavedJobController::save((int) $id)],
    ['DELETE', '/jobs/{id}/save',        fn($id) => SavedJobController::unsave((int) $id)],
    ['GET',    '/jobs/{id}/is-saved',    fn($id) => SavedJobController::isSaved((int) $id)],

    // ---- Job skills ----
    ['GET', '/jobs/{id}/skills',  fn($id) => JobController::getSkills((int) $id)],
    ['PUT', '/jobs/{id}/skills',  fn($id) => JobController::setSkills((int) $id)],

    // ---- Talent requests ----
    ['POST', '/hire-request',                  fn() => TalentRequestController::submit()],
    ['GET',  '/admin/talent-requests',         fn() => TalentRequestController::index()],
    ['GET',  '/admin/talent-requests/{id}',    fn($id) => TalentRequestController::show((int) $id)],
    ['PATCH','/admin/talent-requests/{id}/status', fn($id) => TalentRequestController::updateStatus((int) $id)],
    ['POST', '/admin/talent-requests/{id}/link-job', fn($id) => TalentRequestController::linkJob((int) $id)],
    ['DELETE', '/jobs/{id}',      fn($id) => JobController::destroy((int) $id)],
];
