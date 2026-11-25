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

        // Cloudflare Turnstile verification
        $turnstileToken = trim((string) ($data['turnstile_token'] ?? ''));
        $turnstileSecret = env('TURNSTILE_SECRET_KEY');

        if ($turnstileToken === '' || !$turnstileSecret) {
            json_out([
                'ok' => false,
                'error' => 'Please complete the security verification.'
            ], 422);
        }

        $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'secret' => $turnstileSecret,
                'response' => $turnstileToken,
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $verification = is_string($response)
            ? json_decode($response, true)
            : null;

        if (
            $httpCode !== 200 ||
            !is_array($verification) ||
            ($verification['success'] ?? false) !== true
        ) {
            json_out([
                'ok' => false,
                'error' => 'Security verification failed. Please try again.'
            ], 422);
        }

        // Do not pass the CAPTCHA token into the database model.
        unset($data['turnstile_token']);

        // Validate required fields
        $required = [
            'contact_name',
            'contact_email',
            'company_name',
            'job_title',
            'job_description'
        ];

        foreach ($required as $field) {
            if (trim((string) ($data[$field] ?? '')) === '') {
                json_out([
                    'ok' => false,
                    'error' => "Field '$field' is required"
                ], 422);
            }
        }

        if (!filter_var($data['contact_email'], FILTER_VALIDATE_EMAIL)) {
            json_out([
                'ok' => false,
                'error' => 'Invalid email address'
            ], 422);
        }

        // Validate timeline
        $timeline = $data['timeline'] ?? 'flexible';

        if (!in_array($timeline, self::TIMELINES, true)) {
            $timeline = 'flexible';
        }

        // Validate preferred contact method
        $contact = $data['preferred_contact'] ?? 'email';

        if (!in_array($contact, self::CONTACTS, true)) {
            $contact = 'email';
        }

        $data['timeline'] = $timeline;
        $data['preferred_contact'] = $contact;

        // Save employer request
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

        json_out([
            'ok' => true,
            'id' => $id,
            'message' => "Thanks! We'll be in touch within 1 business day."
        ], 201);
    }

    /** Admin — list all requests. */
    public static function index(): void
    {
        require_role('super_admin');

        $filters = ['status' => $_GET['status'] ?? null];

        json_out([
            'ok' => true,
            'data' => TalentRequest::all($filters)
        ]);
    }

    /** Admin — single request detail. */
    public static function show(int $id): void
    {
        require_role('super_admin');

        $req = TalentRequest::find($id);

        if (!$req) {
            json_out([
                'ok' => false,
                'error' => 'Talent request not found'
            ], 404);
        }

        json_out([
            'ok' => true,
            'request' => $req
        ]);
    }

    /** Admin — update status. */
    public static function updateStatus(int $id): void
    {
        $payload = require_role('super_admin');
        $data = read_json();

        $status = trim((string) ($data['status'] ?? ''));
        $notes = isset($data['admin_notes'])
            ? trim((string) $data['admin_notes'])
            : null;

        if ($status === '') {
            json_out([
                'ok' => false,
                'error' => 'Status is required'
            ], 422);
        }

        try {
            $ok = TalentRequest::updateStatus(
                $id,
                $status,
                $notes,
                (int) $payload['sub']
            );
        } catch (InvalidArgumentException $e) {
            json_out([
                'ok' => false,
                'error' => $e->getMessage()
            ], 422);
        }

        if (!$ok) {
            json_out([
                'ok' => false,
                'error' => 'Not found or no change'
            ], 404);
        }

        $updated = TalentRequest::find($id);

        json_out([
            'ok' => true,
            'request' => $updated
        ]);
    }

    /** Admin — link a created job to this request. */
    public static function linkJob(int $id): void
    {
        require_role('super_admin');

        $data = read_json();
        $jobId = isset($data['job_id']) ? (int) $data['job_id'] : 0;

        if (!$jobId) {
            json_out([
                'ok' => false,
                'error' => 'job_id is required'
            ], 422);
        }

        $job = Job::findById($jobId);

        if (!$job) {
            json_out([
                'ok' => false,
                'error' => 'Job not found'
            ], 404);
        }

        TalentRequest::linkJob($id, $jobId);
        $req = TalentRequest::find($id);

        json_out([
            'ok' => true,
            'request' => $req
        ]);
    }
}
