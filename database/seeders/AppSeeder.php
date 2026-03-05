<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Church;
use App\Models\Section;
use App\Models\Department;
use App\Models\User;
use App\Models\Requisition;
use App\Models\Approval;
use App\Models\Payment;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AppSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Default password for all seeded users
        $password = Hash::make('password');

        // --- 1. CHURCH (From mockData.ts MOCK_CHURCH) ---
        $church = Church::create([
            'name' => 'Grace Community Cathedral',
            'subscription_status' => 'Active',
            'subscription_ends_at' => '2026-12-31 23:59:59',
        ]);

        // --- 2. SECTIONS & DEPARTMENTS (From mockData.ts MOCK_CHURCH.sections) ---
        // Section: Youth Section
        $sectionYouth = Section::create([
            'church_id' => $church->id,
            'name' => 'Youth Section',
        ]);

        $deptMusic = Department::create([
            'section_id' => $sectionYouth->id,
            'name' => 'Music Ministry',
        ]);

        $deptMedia = Department::create([
            'section_id' => $sectionYouth->id,
            'name' => 'Media & Tech',
        ]);

        // Section: Main Tabernacle
        $sectionMain = Section::create([
            'church_id' => $church->id,
            'name' => 'Main Tabernacle',
        ]);

        $deptUshers = Department::create([
            'section_id' => $sectionMain->id,
            'name' => 'Ushers',
        ]);

        $deptWelfare = Department::create([
            'section_id' => $sectionMain->id,
            'name' => 'Welfare',
        ]);

        // --- 3. USERS (From mockData.ts MOCK_USERS) ---
        // John Member
        $userMember = User::create([
            'church_id' => $church->id,
            'section_id' => $sectionYouth->id,
            'department_id' => $deptMusic->id,
            'name' => 'John Member',
            'email' => 'member@church.com',
            'password' => $password,
            'role' => 'Member',
        ]);

        // Sarah Head
        $userDeptHead = User::create([
            'church_id' => $church->id,
            'section_id' => $sectionYouth->id,
            'department_id' => $deptMusic->id,
            'name' => 'Sarah Head',
            'email' => 'depthead@church.com',
            'password' => $password,
            'role' => 'Department Head',
        ]);

        // Pastor James
        $userPresident = User::create([
            'church_id' => $church->id,
            'section_id' => $sectionYouth->id,
            'name' => 'Pastor James',
            'email' => 'president@church.com',
            'password' => $password,
            'role' => 'Section President',
        ]);

        // Deborah Accountant
        $userFinance = User::create([
            'church_id' => $church->id,
            'section_id' => $sectionYouth->id,
            'name' => 'Deborah Accountant',
            'email' => 'finance@church.com',
            'password' => $password,
            'role' => 'Finance',
        ]);

        // Mr. Audit
        $userAuditor = User::create([
            'church_id' => $church->id,
            'name' => 'Mr. Audit',
            'email' => 'auditor@church.com',
            'password' => $password,
            'role' => 'Auditor',
        ]);

        // Super Admin
        $userAdmin = User::create([
            'church_id' => $church->id,
            'name' => 'Super Admin',
            'email' => 'admin@church.com',
            'password' => $password,
            'role' => 'Super Admin',
        ]);

        // Platform Owner
        $userOwner = User::create([
            'church_id' => $church->id,
            'name' => 'Platform Owner',
            'email' => 'owner@church.com',
            'password' => $password,
            'role' => 'App Owner',
        ]);

        // --- 4. REQUISITIONS (From mockData.ts MOCK_REQUISITIONS) ---
        // Req-1: New Microphones for Youth Choir
        $req1 = Requisition::create([
            'title' => 'New Microphones for Youth Choir',
            'requested_by_id' => $userMember->id,
            'department_id' => $deptMusic->id,
            'section_id' => $sectionYouth->id,
            'church_id' => $church->id,
            'amount_requested' => 150000,
            'category' => 'Equipment',
            'purpose' => 'Replacing 3 faulty microphones for the youth section choir.',
            'date_needed' => '2026-03-20',
            'status' => 'Pending',
        ]);

        // Req-2: Generator Maintenance
        $req2 = Requisition::create([
            'title' => 'Generator Maintenance',
            'requested_by_id' => $userDeptHead->id,
            'department_id' => $deptMedia->id,
            'section_id' => $sectionYouth->id,
            'church_id' => $church->id,
            'amount_requested' => 45000,
            'category' => 'Maintenance',
            'purpose' => 'Quarterly servicing of the 50KVA generator.',
            'date_needed' => '2026-03-15',
            'status' => 'Approved by Dept. Head',
        ]);

        // Approval for Req-2
        Approval::create([
            'requisition_id' => $req2->id,
            'approver_id' => $userDeptHead->id,
            'status' => 'APPROVED',
            'comments' => 'Urgent maintenance needed.',
            'timestamp' => '2026-03-02 09:00:00',
        ]);

        // Req-3: Welfare Outreach Program
        $req3 = Requisition::create([
            'title' => 'Welfare Outreach Program',
            'requested_by_id' => $userDeptHead->id,
            'department_id' => $deptWelfare->id,
            'section_id' => $sectionMain->id,
            'church_id' => $church->id,
            'amount_requested' => 300000,
            'category' => 'Mission',
            'purpose' => 'Community food bank for 50 families.',
            'date_needed' => '2026-04-01',
            'status' => 'Approved by Section President',
        ]);

        // --- 5. AUDIT LOGS (Basic logs mirroring actions) ---
        AuditLog::create([
            'user_id' => $userMember->id,
            'church_id' => $church->id,
            'requisition_id' => $req1->id,
            'action' => 'REQUISITION_CREATED',
            'details' => "Requisition 'New Microphones for Youth Choir' submitted.",
        ]);

        AuditLog::create([
            'user_id' => $userDeptHead->id,
            'church_id' => $church->id,
            'requisition_id' => $req2->id,
            'action' => 'REQUISITION_APPROVED',
            'details' => "Requisition approved by Department Head (Sarah Head).",
        ]);
    }
}
