<?php

namespace App\Services;

use App\Models\Church;
use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ChurchService
{
    /**
     * Get church details, ensuring the user belongs to it.
     * @param int $churchId
     * @param User $user
     * @return Church
     */
    public function getChurch(string $churchId, User $user): Church
    {
        if ($user->church_id !== $churchId && $user->role !== 'App Owner') {
            throw new \Exception('Access denied to this church data.');
        }

        return Church::with(['sections.departments', 'users'])->findOrFail($churchId);
    }

    /**
     * Create a new section within a church.
     * @param int $churchId
     * @param string $name
     * @param User $user
     * @return Section
     * @throws \Exception
     */
    public function createSection(int $churchId, string $name, User $user): Section
    {
        if ($user->church_id !== $churchId || !in_array($user->role, ['Super Admin', 'Section President'])) {
            throw new \Exception('Unauthorized to create sections in this church.');
        }

        $section = Section::create([
            'church_id' => $churchId,
            'name' => $name,
        ]);

        AuditLogService::log($user, 'SECTION_CREATED', "New section '{$name}' created in Church ID: {$churchId}.");

        return $section;
    }

    /**
     * Create a new user within a church.
     * @param array $userData
     * @param User $adminUser
     * @return User
     * @throws \Exception
     */
    public function createUser(array $userData, User $adminUser): User
    {
        if (!in_array($adminUser->role, ['Super Admin', 'Section President'])) {
            throw new \Exception('Unauthorized to create users.');
        }

        // Ensure new user belongs to the admin's church
        $userData['church_id'] = $adminUser->church_id;
        $userData['password'] = Hash::make($userData['password']);

        $newUser = User::create($userData);

        AuditLogService::log($adminUser, 'USER_CREATED', "New user '{$newUser->email}' created with role {$newUser->role}.");

        return $newUser;
    }

    /**
     * Extend church subscription.
     * @param Church $church
     * @param int $months
     * @param User $user
     * @return Church
     */
    public function extendSubscription(Church $church, int $months, User $user): Church
    {
        if (!in_array($user->role, ['Super Admin', 'App Owner'])) {
            throw new \Exception('Only Super Admins or App Owners can extend subscriptions.');
        }

        $newEndDate = $church->subscription_ends_at ?
            $church->subscription_ends_at->addMonths($months) :
            now()->addMonths($months);

        $church->update([
            'subscription_status' => 'Active',
            'subscription_ends_at' => $newEndDate,
        ]);

        AuditLogService::log($user, 'SUBSCRIPTION_EXTENDED', "Subscription extended by {$months} months.");

        return $church;
    }

    /**
     * Create a new department within a section.
     * @param string $sectionId
     * @param string $name
     * @param User $user
     * @return Department
     * @throws \Exception
     */
    public function createDepartment(string $sectionId, string $name, User $user): \App\Models\Department
    {
        $section = Section::findOrFail($sectionId);

        if ($user->church_id !== $section->church_id || !in_array($user->role, ['Super Admin', 'Section President'])) {
            throw new \Exception('Unauthorized to create departments in this section.');
        }

        $department = \App\Models\Department::create([
            'section_id' => $sectionId,
            'name' => $name,
        ]);

        AuditLogService::log($user, 'DEPARTMENT_CREATED', "New department '{$name}' created in Section ID: {$sectionId}.");

        return $department;
    }

    /**
     * Get Audit Logs for a church.
     * @param Church $church
     * @param User $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAuditLogs(Church $church, User $user)
    {
        if ($user->church_id !== $church->id && $user->role !== 'App Owner') {
            throw new \Exception('Access denied to church audit logs.');
        }

        return $church->auditLogs()->with('user')->latest()->get();
    }
}
