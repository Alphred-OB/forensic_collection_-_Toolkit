<?php

namespace Tests\Feature;

use App\Models\ForensicCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_case_search_and_status_filtering()
    {
        $user = User::factory()->create(['role' => 'Administrator']);

        $case1 = ForensicCase::create(['case_number' => 'CASE-101', 'title' => 'Alpha Malware Breach', 'status' => 'open', 'created_by' => $user->id]);
        $case2 = ForensicCase::create(['case_number' => 'CASE-102', 'title' => 'Beta Financial Fraud', 'status' => 'closed', 'created_by' => $user->id]);

        $responseSearch = $this->actingAs($user)->get(route('cases.index', ['search' => 'Malware']));
        $responseSearch->assertSee('CASE-101');
        $responseSearch->assertDontSee('CASE-102');

        $responseStatus = $this->actingAs($user)->get(route('cases.index', ['status' => 'closed']));
        $responseStatus->assertSee('CASE-102');
        $responseStatus->assertDontSee('CASE-101');
    }

    public function test_team_assignment_and_status_update()
    {
        $admin = User::factory()->create(['role' => 'Administrator']);
        $investigator = User::factory()->create(['role' => 'Investigator']);

        $case = ForensicCase::create(['case_number' => 'CASE-201', 'title' => 'Ransomware Outbreak', 'status' => 'open', 'created_by' => $admin->id]);

        // Update team
        $responseTeam = $this->actingAs($admin)->post(route('cases.update-team', $case->id), [
            'assigned_users' => [$admin->id, $investigator->id],
        ]);
        $responseTeam->assertRedirect();
        $this->assertTrue($case->fresh()->assignedUsers->contains($investigator->id));

        // Update status to archived
        $responseStatus = $this->actingAs($admin)->post(route('cases.update-status', $case->id), [
            'status' => 'archived',
        ]);
        $responseStatus->assertRedirect();
        $this->assertEquals('archived', $case->fresh()->status);
    }

    public function test_case_creation_with_priority_and_tags()
    {
        $admin = User::factory()->create(['role' => 'Administrator']);

        $responseCreate = $this->actingAs($admin)->post(route('cases.store'), [
            'case_number' => 'CASE-999',
            'title' => 'Critical Ransomware Outbreak',
            'description' => 'Server room encrypted.',
            'priority' => 'Critical',
            'tags' => 'Cyber Crime, Ransomware, Critical Infra',
        ]);

        $responseCreate->assertRedirect();
        $this->assertDatabaseHas('cases', [
            'case_number' => 'CASE-999',
            'priority' => 'Critical',
            'tags' => 'Cyber Crime, Ransomware, Critical Infra',
        ]);
    }

    public function test_archived_case_blocks_evidence_upload_and_team_edits()
    {
        $admin = User::factory()->create(['role' => 'Administrator']);
        $case = ForensicCase::create([
            'case_number' => 'CASE-CLOSED-1',
            'title' => 'Archived Fraud Case',
            'status' => 'archived',
            'created_by' => $admin->id,
        ]);

        $responseUploadPage = $this->actingAs($admin)->get(route('evidence.create', $case->id));
        $responseUploadPage->assertStatus(403);

        $responseUpdateTeam = $this->actingAs($admin)->post(route('cases.update-team', $case->id), [
            'assigned_users' => [$admin->id],
        ]);
        $responseUpdateTeam->assertStatus(403);
    }

    public function test_user_can_generate_case_final_report()
    {
        $admin = User::factory()->create(['role' => 'Administrator']);
        $case = ForensicCase::create([
            'case_number' => 'CASE-REPORT-1',
            'title' => 'Report Generation Test',
            'created_by' => $admin->id,
        ]);

        $responseReport = $this->actingAs($admin)->get(route('reports.case', $case->id));
        $responseReport->assertStatus(200);
        $responseReport->assertSee('FORENSIC TOOLKIT - OFFICIAL CASE REPORT');
        $this->assertDatabaseHas('reports', [
            'case_id' => $case->id,
            'type' => 'final_report',
        ]);
    }

    public function test_case_edit_update_and_admin_delete()
    {
        $admin = User::factory()->create(['role' => 'Administrator']);
        $case = ForensicCase::create([
            'case_number' => 'CASE-EDIT-1',
            'title' => 'Initial Title',
            'priority' => 'Normal',
            'tags' => 'tag1',
            'created_by' => $admin->id,
        ]);

        $responseEdit = $this->actingAs($admin)->get(route('cases.edit', $case->id));
        $responseEdit->assertStatus(200);

        $responseUpdate = $this->actingAs($admin)->put(route('cases.update', $case->id), [
            'title' => 'Updated Title',
            'description' => 'Updated description.',
            'priority' => 'Critical',
            'tags' => 'Custom Tag 1, Custom Tag 2',
        ]);
        $responseUpdate->assertRedirect(route('cases.show', $case->id));
        $this->assertDatabaseHas('cases', [
            'id' => $case->id,
            'title' => 'Updated Title',
            'priority' => 'Critical',
            'tags' => 'Custom Tag 1, Custom Tag 2',
        ]);

        $responseDelete = $this->actingAs($admin)->delete(route('cases.destroy', $case->id));
        $responseDelete->assertRedirect(route('cases.index'));
        $this->assertSoftDeleted('cases', ['id' => $case->id]);

        $responseRestore = $this->actingAs($admin)->post(route('cases.restore', $case->id));
        $responseRestore->assertRedirect();
        $this->assertDatabaseHas('cases', ['id' => $case->id, 'deleted_at' => null]);
    }
}
