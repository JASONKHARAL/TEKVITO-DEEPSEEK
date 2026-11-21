<?php
// backend/controllers/AuthController.php

declare(strict_types=1);

final class AuthController
{
    public static function login(): void
    {
        $data = read_json();
        $email = trim((string) ($data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        if ($email === '' || $password === '') {
            json_out(['ok' => false, 'error' => 'Email and password are required'], 422);
        }

        $user = User::verifyPassword($email, $password);
        if (!$user) {
            json_out(['ok' => false, 'error' => 'Invalid credentials'], 401);
        }

        $roles = User::getRoles((int) $user['id']);
        $token = JwtService::sign([
            'sub'   => $user['id'],
            'email' => $user['email'],
            'roles' => $roles,
        ]);

        json_out([
            'ok'    => true,
            'token' => $token,
            'user'  => [
                'id'         => (int) $user['id'],
                'email'      => $user['email'],
                'first_name' => $user['first_name'],
                'last_name'  => $user['last_name'],
                'roles'      => $roles,
            ],
        ]);
    }

    public static function register(): void
    {
        $data = read_json();
        $email     = trim((string) ($data['email'] ?? ''));
        $password  = (string) ($data['password'] ?? '');
        $firstName = trim((string) ($data['first_name'] ?? ''));
        $lastName  = trim((string) ($data['last_name'] ?? ''));

        if ($email === '' || $password === '' || $firstName === '' || $lastName === '') {
            json_out(['ok' => false, 'error' => 'All fields are required'], 422);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_out(['ok' => false, 'error' => 'Invalid email format'], 422);
        }
        if (strlen($password) < 8) {
            json_out(['ok' => false, 'error' => 'Password must be at least 8 characters'], 422);
        }
        if (User::findByEmail($email)) {
            json_out(['ok' => false, 'error' => 'Email already registered'], 409);
        }

        $userId = User::create($email, $password, $firstName, $lastName);
        $roles = User::getRoles($userId);
        $token = JwtService::sign([
            'sub'   => $userId,
            'email' => $email,
            'roles' => $roles,
        ]);

        json_out([
            'ok'    => true,
            'token' => $token,
            'user'  => [
                'id'         => $userId,
                'email'      => $email,
                'first_name' => $firstName,
                'last_name'  => $lastName,
                'roles'      => $roles,
            ],
        ], 201);
    }

    public static function me(): void
    {
        $payload = require_auth();
        $user = User::findById((int) $payload['sub']);
        if (!$user) {
            json_out(['ok' => false, 'error' => 'User not found'], 404);
        }
        $user['roles'] = User::getRoles((int) $user['id']);
        $user['permissions'] = User::getPermissions((int) $user['id']);
        json_out(['ok' => true, 'user' => $user]);
    }
}