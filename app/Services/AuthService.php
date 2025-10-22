<?php

namespace App\Services;

use App\Models\Church;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Handle user login and token generation.
     * @param string $email
     * @param string $password
     * @return array{'user': User, 'token': string}
     * @throws ValidationException
     */
    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match our records.'],
            ]);
        }

        // Revoke old tokens and create a new one
        $user->tokens()->delete();
        $token = $user->createToken('api-token')->plainTextToken;

        AuditLogService::log($user, 'USER_LOGIN', 'User successfully logged in.');

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Register a new church and its Super Admin user.
     * @param string $churchName
     * @param string $adminName
     * @param string $adminEmail
     * @param string $adminPassword
     * @return User
     */
    public function registerChurch(string $churchName, string $adminName, string $adminEmail, string $adminPassword): User
    {
        return DB::transaction(function () use ($churchName, $adminName, $adminEmail, $adminPassword) {
            // 1. Create Church (Trial subscription by default)
            $church = Church::create([
                'name' => $churchName,
                'subscription_status' => 'Trial',
                'subscription_ends_at' => now()->addDays(30), // 30-day trial
            ]);

            // 2. Create Super Admin User
            $user = User::create([
                'name' => $adminName,
                'email' => $adminEmail,
                'password' => Hash::make($adminPassword),
                'role' => 'Super Admin',
                'church_id' => $church->id,
                'section_id' => null,
                'department_id' => null,
            ]);

            AuditLogService::log($user, 'CHURCH_REGISTERED', 'New church and Super Admin registered.');

            return $user;
        });
    }
}
