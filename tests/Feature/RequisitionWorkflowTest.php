<?php

namespace Tests\Feature;

use App\Models\Church;
use App\Models\Department;
use App\Models\Requisition;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RequisitionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_department_head_approval_persists_enum_mapped_status(): void
    {
        [$church, $section, $department] = $this->createChurchHierarchy();
        $member = $this->createUser($church, 'Member', $section, $department);
        $deptHead = $this->createUser($church, 'Department Head', $section, $department);

        $requisition = Requisition::create([
            'title' => 'Speaker repair',
            'requested_by_id' => $member->id,
            'department_id' => $department->id,
            'section_id' => $section->id,
            'church_id' => $church->id,
            'amount_requested' => 25000,
            'category' => 'Maintenance',
            'purpose' => 'Repair damaged speakers',
            'date_needed' => now()->addDays(3)->toDateString(),
            'status' => 'Pending',
        ]);

        Sanctum::actingAs($deptHead);

        $response = $this->postJson("/api/requisitions/{$requisition->id}/action", [
            'action' => 'APPROVE',
            'comments' => 'Looks good',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.status', 'Approved by Dept. Head');

        $this->assertDatabaseHas('approvals', [
            'requisition_id' => $requisition->id,
            'approver_id' => $deptHead->id,
            'status' => 'APPROVED',
        ]);
    }

    public function test_department_head_cannot_approve_requisition_outside_their_department_scope(): void
    {
        [$church, $section] = $this->createChurchHierarchy();
        $departmentA = Department::create(['section_id' => $section->id, 'name' => 'Media']);
        $departmentB = Department::create(['section_id' => $section->id, 'name' => 'Welfare']);
        $member = $this->createUser($church, 'Member', $section, $departmentB);
        $deptHead = $this->createUser($church, 'Department Head', $section, $departmentA);

        $requisition = Requisition::create([
            'title' => 'Food packs',
            'requested_by_id' => $member->id,
            'department_id' => $departmentB->id,
            'section_id' => $section->id,
            'church_id' => $church->id,
            'amount_requested' => 50000,
            'category' => 'Outreach',
            'purpose' => 'Distribute food packs',
            'date_needed' => now()->addDays(2)->toDateString(),
            'status' => 'Pending',
        ]);

        Sanctum::actingAs($deptHead);

        $response = $this->postJson("/api/requisitions/{$requisition->id}/action", [
            'action' => 'APPROVE',
        ]);

        $response->assertForbidden()
            ->assertJsonPath('status', false);
    }

    public function test_user_cannot_view_requisition_from_another_church(): void
    {
        [$churchA, $sectionA, $departmentA] = $this->createChurchHierarchy('Church A');
        [$churchB, $sectionB, $departmentB] = $this->createChurchHierarchy('Church B');

        $viewer = $this->createUser($churchA, 'Member', $sectionA, $departmentA);
        $memberB = $this->createUser($churchB, 'Member', $sectionB, $departmentB);

        $requisition = Requisition::create([
            'title' => 'Projector',
            'requested_by_id' => $memberB->id,
            'department_id' => $departmentB->id,
            'section_id' => $sectionB->id,
            'church_id' => $churchB->id,
            'amount_requested' => 100000,
            'category' => 'Equipment',
            'purpose' => 'Buy a new projector',
            'date_needed' => now()->addWeek()->toDateString(),
            'status' => 'Pending',
        ]);

        Sanctum::actingAs($viewer);

        $this->getJson("/api/requisitions/{$requisition->id}")
            ->assertForbidden()
            ->assertJsonPath('status', false);
    }

    public function test_finance_disburse_accepts_camel_case_payment_payload(): void
    {
        [$church, $section, $department] = $this->createChurchHierarchy();
        $requester = $this->createUser($church, 'Member', $section, $department);
        $finance = $this->createUser($church, 'Finance', $section);

        $requisition = Requisition::create([
            'title' => 'Generator servicing',
            'requested_by_id' => $requester->id,
            'department_id' => $department->id,
            'section_id' => $section->id,
            'church_id' => $church->id,
            'amount_requested' => 70000,
            'category' => 'Maintenance',
            'purpose' => 'Quarterly generator servicing',
            'date_needed' => now()->addDays(5)->toDateString(),
            'status' => 'Approved by Section President',
        ]);

        Sanctum::actingAs($finance);

        $response = $this->postJson("/api/requisitions/{$requisition->id}/disburse", [
            'paymentDetails' => [
                'amountPaid' => 70000,
                'paymentMethod' => 'Bank Transfer',
                'paymentDate' => now()->toDateString(),
                'referenceNumber' => 'REF-12345',
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.status', 'Awaiting Receipt')
            ->assertJsonPath('data.payment.payment_method', 'Bank Transfer');

        $this->assertDatabaseHas('payments', [
            'requisition_id' => $requisition->id,
            'amount_paid' => 70000,
            'payment_method' => 'Bank Transfer',
            'reference_number' => 'REF-12345',
            'recorded_by_id' => $finance->id,
        ]);
    }

    private function createChurchHierarchy(string $name = 'Grace Community Cathedral'): array
    {
        $church = Church::create([
            'name' => $name,
            'subscription_status' => 'Active',
            'subscription_ends_at' => now()->addYear(),
        ]);

        $section = Section::create([
            'church_id' => $church->id,
            'name' => 'Main Section',
        ]);

        $department = Department::create([
            'section_id' => $section->id,
            'name' => 'Media',
        ]);

        return [$church, $section, $department];
    }

    private function createUser(
        Church $church,
        string $role,
        ?Section $section = null,
        ?Department $department = null
    ): User {
        return User::create([
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password123',
            'role' => $role,
            'church_id' => $church->id,
            'section_id' => $section?->id,
            'department_id' => $department?->id,
        ]);
    }
}
