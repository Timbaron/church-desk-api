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

        // --- 1. CHURCHES (3 Churches) ---
        $church1Id = Str::uuid();
        $church1 = Church::create([
            'id' => $church1Id,
            'name' => 'Main City Assembly',
            'subscription_status' => 'Active',
            'subscription_ends_at' => now()->addYears(1),
        ]);

        $church2Id = Str::uuid();
        $church2 = Church::create([
            'id' => $church2Id,
            'name' => 'Central Cathedral',
            'subscription_status' => 'Trial',
            'subscription_ends_at' => now()->addDays(20),
        ]);

        $church3Id = Str::uuid(); // New third church
        $church3 = Church::create([
            'id' => $church3Id,
            'name' => 'Suburban Chapel',
            'subscription_status' => 'Expired',
            'subscription_ends_at' => now()->subDays(5),
        ]);

        // --- 2. SECTIONS (Each with 3 Ministry Sections + 1 Admin Section for Church 1) ---

        // Church 1 Sections
        $sectionAdminId = Str::uuid();
        $sectionAdmin = Section::create(['id' => $sectionAdminId, 'church_id' => $church1Id, 'name' => 'Administration & Services']);

        $sectionYouth1Id = Str::uuid();
        $sectionYouth1 = Section::create(['id' => $sectionYouth1Id, 'church_id' => $church1Id, 'name' => 'Youth Ministry']);

        $sectionChildren1Id = Str::uuid();
        $sectionChildren1 = Section::create(['id' => $sectionChildren1Id, 'church_id' => $church1Id, 'name' => "Children's Ministry"]);

        $sectionWomen1Id = Str::uuid();
        $sectionWomen1 = Section::create(['id' => $sectionWomen1Id, 'church_id' => $church1Id, 'name' => "Women's Ministry"]);

        // Church 2 Sections (3 Sections)
        $sectionYouth2Id = Str::uuid();
        $sectionYouth2 = Section::create(['id' => $sectionYouth2Id, 'church_id' => $church2Id, 'name' => 'Youth Ministry']);

        $sectionChildren2Id = Str::uuid();
        $sectionChildren2 = Section::create(['id' => $sectionChildren2Id, 'church_id' => $church2Id, 'name' => "Children's Ministry"]);

        $sectionWomen2Id = Str::uuid();
        $sectionWomen2 = Section::create(['id' => $sectionWomen2Id, 'church_id' => $church2Id, 'name' => "Women's Ministry"]);

        // Church 3 Sections (3 Sections)
        $sectionYouth3Id = Str::uuid();
        $sectionYouth3 = Section::create(['id' => $sectionYouth3Id, 'church_id' => $church3Id, 'name' => 'Youth Ministry']);

        $sectionChildren3Id = Str::uuid();
        $sectionChildren3 = Section::create(['id' => $sectionChildren3Id, 'church_id' => $church3Id, 'name' => "Children's Ministry"]);

        $sectionWomen3Id = Str::uuid();
        $sectionWomen3 = Section::create(['id' => $sectionWomen3Id, 'church_id' => $church3Id, 'name' => "Women's Ministry"]);


        // --- 3. DEPARTMENTS (Attached to Church 1 Admin/Ministry Sections) ---
        $deptFinanceId = Str::uuid();
        $deptFinance = Department::create(['id' => $deptFinanceId, 'section_id' => $sectionAdminId, 'name' => 'Finance']);

        $deptHRId = Str::uuid();
        $deptHR = Department::create(['id' => $deptHRId, 'section_id' => $sectionAdminId, 'name' => 'Human Resources']);

        $deptEventsId = Str::uuid();
        $deptEvents = Department::create(['id' => $deptEventsId, 'section_id' => $sectionYouth1Id, 'name' => 'Outreach & Events']);

        $deptMediaId = Str::uuid();
        $deptMedia = Department::create(['id' => $deptMediaId, 'section_id' => $sectionYouth1Id, 'name' => 'Media Production']);

        // --- 4. USERS (All attached to Church 1) ---
        $superAdminId = Str::uuid();
        $superAdmin = User::create([
            'id' => $superAdminId,
            'church_id' => $church1Id,
            'name' => 'Alice Admin',
            'email' => 'admin@church.com',
            'password' => $password,
            'role' => 'Super Admin',
        ]);

        $financeUserId = Str::uuid();
        $financeUser = User::create([
            'id' => $financeUserId,
            'church_id' => $church1Id,
            'section_id' => $sectionAdminId,
            'department_id' => $deptFinanceId,
            'name' => 'Finley Finance',
            'email' => 'finance@church.com',
            'password' => $password,
            'role' => 'Finance',
        ]);

        $sectionPresidentId = Str::uuid();
        $sectionPresident = User::create([
            'id' => $sectionPresidentId,
            'church_id' => $church1Id,
            'section_id' => $sectionYouth1Id,
            'name' => 'Sarah President',
            'email' => 'president@church.com',
            'password' => $password,
            'role' => 'Section President',
        ]);

        $deptHeadId = Str::uuid();
        $deptHead = User::create([
            'id' => $deptHeadId,
            'church_id' => $church1Id,
            'section_id' => $sectionYouth1Id,
            'department_id' => $deptEventsId,
            'name' => 'David Head',
            'email' => 'depthead@church.com',
            'password' => $password,
            'role' => 'Department Head',
        ]);

        $requesterId = Str::uuid();
        $requester = User::create([
            'id' => $requesterId,
            'church_id' => $church1Id,
            'section_id' => $sectionYouth1Id,
            'department_id' => $deptEventsId,
            'name' => 'Ryan Requester',
            'email' => 'member@church.com',
            'password' => $password,
            'role' => 'Member',
        ]);

        // --- 5. REQUISITIONS (All attached to Church 1) ---
        $reqCompletedId = Str::uuid();
        $reqCompleted = Requisition::create([
            'id' => $reqCompletedId,
            'title' => 'Youth Retreat Catering Payment',
            'requested_by_id' => $requesterId,
            'department_id' => $deptEventsId,
            'section_id' => $sectionYouth1Id,
            'church_id' => $church1Id,
            'amount_requested' => 1500.50,
            'category' => 'Events',
            'purpose' => 'Payment for the catering service for the 2024 Youth Retreat.',
            'date_needed' => now()->subDays(5),
            'status' => 'Completed',
            'attachments' => json_encode(['file1.pdf', 'invoice.jpg']),
        ]);

        // Approvals for Completed Requisition
        Approval::create([
            'requisition_id' => $reqCompletedId,
            'approver_id' => $deptHeadId,
            'status' => 'APPROVED',
            'comments' => 'Necessary and budget confirmed.',
        ]);
        Approval::create([
            'requisition_id' => $reqCompletedId,
            'approver_id' => $sectionPresidentId,
            'status' => 'APPROVED',
            'comments' => 'Final review and sign-off.',
        ]);

        // Payment for Completed Requisition
        Payment::create([
            'requisition_id' => $reqCompletedId,
            'amount_paid' => 1500.50,
            'payment_method' => 'Bank Transfer',
            'payment_date' => now()->subDays(4),
            'reference_number' => 'BT102025001',
            'recorded_by_id' => $financeUserId,
        ]);

        // 5b. Pending Section President Approval - Media Department (Youth Section)
        $reqSectionApprovedId = Str::uuid();
        $reqSectionApproved = Requisition::create([
            'id' => $reqSectionApprovedId,
            'title' => 'Purchase New Lighting Equipment',
            'requested_by_id' => $deptHeadId,
            'department_id' => $deptMediaId,
            'section_id' => $sectionYouth1Id,
            'church_id' => $church1Id,
            'amount_requested' => 3500.00,
            'category' => 'Capital Expenditure',
            'purpose' => 'Upgrade stage lighting for Sunday services.',
            'date_needed' => now()->addDays(10),
            'status' => 'Approved by Dept. Head',
        ]);
        Approval::create([
            'requisition_id' => $reqSectionApprovedId,
            'approver_id' => $deptHeadId,
            'status' => 'APPROVED',
            'comments' => 'Critical hardware upgrade. Requesting Section President sign-off.',
        ]);

        // 5c. Pending Department Head Approval - HR Department (Admin Section)
        $reqPendingId = Str::uuid();
        Requisition::create([
            'id' => $reqPendingId,
            'title' => 'Monthly Office Supply Restock',
            'requested_by_id' => $requesterId,
            'department_id' => $deptHRId,
            'section_id' => $sectionAdminId,
            'church_id' => $church1Id,
            'amount_requested' => 250.00,
            'category' => 'Operational',
            'purpose' => 'Restock paper, toner, and basic stationery for HR.',
            'date_needed' => now()->addDays(5),
            'status' => 'Pending',
        ]);

        // 5d. Rejected Requisition - Youth Ministry
        $reqRejectedId = Str::uuid();
        $reqRejected = Requisition::create([
            'id' => $reqRejectedId,
            'title' => 'New Coffee Machine for Lounge',
            'requested_by_id' => $requesterId,
            'department_id' => $deptEventsId,
            'section_id' => $sectionYouth1Id,
            'church_id' => $church1Id,
            'amount_requested' => 800.00,
            'category' => 'Welfare',
            'purpose' => 'Upgrade the staff lounge coffee machine.',
            'date_needed' => now()->addDays(2),
            'status' => 'Rejected',
        ]);
        Approval::create([
            'requisition_id' => $reqRejectedId,
            'approver_id' => $deptHeadId,
            'status' => 'REJECTED',
            'comments' => 'Not essential at this time. Please resubmit next quarter.',
        ]);


        // --- 6. AUDIT LOGS ---
        AuditLog::create([
            'user_id' => $superAdminId,
            'church_id' => $church1Id,
            'action' => 'CHURCH_CONFIG',
            'details' => "Updated church subscription to Active.",
        ]);
        AuditLog::create([
            'user_id' => $requesterId,
            'church_id' => $church1Id,
            'requisition_id' => $reqCompletedId,
            'action' => 'REQUISITION_CREATED',
            'details' => "Requisition 'Youth Retreat Catering Payment' submitted.",
        ]);
        AuditLog::create([
            'user_id' => $deptHeadId,
            'church_id' => $church1Id,
            'requisition_id' => $reqCompletedId,
            'action' => 'REQUISITION_APPROVED',
            'details' => "Requisition approved by Department Head.",
        ]);
    }
}
