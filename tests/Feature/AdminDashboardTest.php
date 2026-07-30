<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_users_are_forbidden_from_admin_console()
    {
        $investigator = User::factory()->create(['role' => 'Investigator']);

        $response = $this->actingAs($investigator)->get(route('admin.dashboard'));
        $response->assertStatus(403);
    }

    public function test_admin_can_access_dashboard_and_see_sidebar()
    {
        $admin = User::factory()->create(['role' => 'Administrator']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $response->assertSee('System Administrator Console');
    }

    public function test_admin_full_user_crud_lifecycle()
    {
        $admin = User::factory()->create(['role' => 'Administrator']);

        // 1. CREATE User
        $responseCreate = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Agent Fox Mulder',
            'email' => 'mulder@forensics.org',
            'password' => 'password123',
            'role' => 'Investigator',
        ]);
        $responseCreate->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email' => 'mulder@forensics.org',
            'role' => 'Investigator',
        ]);

        $createdUser = User::where('email', 'mulder@forensics.org')->first();

        // 2. READ / SEARCH User
        $responseSearch = $this->actingAs($admin)->get(route('admin.users.index', ['search' => 'Mulder']));
        $responseSearch->assertStatus(200);
        $responseSearch->assertSee('Agent Fox Mulder');

        // 3. UPDATE User
        $responseUpdate = $this->actingAs($admin)->put(route('admin.users.update', $createdUser->id), [
            'name' => 'Special Agent Fox Mulder',
            'email' => 'mulder.updated@forensics.org',
            'role' => 'Administrator',
        ]);
        $responseUpdate->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $createdUser->id,
            'name' => 'Special Agent Fox Mulder',
            'email' => 'mulder.updated@forensics.org',
            'role' => 'Administrator',
        ]);

        // 4. DELETE User
        $responseDelete = $this->actingAs($admin)->delete(route('admin.users.delete', $createdUser->id));
        $responseDelete->assertRedirect();
        $this->assertDatabaseMissing('users', [
            'id' => $createdUser->id,
        ]);
    }

    public function test_admin_cannot_delete_self()
    {
        $admin = User::factory()->create(['role' => 'Administrator']);

        $responseDelete = $this->actingAs($admin)->delete(route('admin.users.delete', $admin->id));
        $responseDelete->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
        ]);
    }

    public function test_admin_can_run_system_integrity_scan()
    {
        $admin = User::factory()->create(['role' => 'Administrator']);

        $responseScan = $this->actingAs($admin)->post(route('admin.audit.scan'));
        $responseScan->assertRedirect();
        $responseScan->assertSessionHas('scan_results');
    }

    public function test_admin_can_export_audit_log_csv()
    {
        $admin = User::factory()->create(['role' => 'Administrator']);

        $responseExport = $this->actingAs($admin)->get(route('admin.audit.export-csv'));
        $responseExport->assertStatus(200);
        $responseExport->assertHeader('content-type', 'text/csv; charset=utf-8');
    }

    public function test_admin_can_access_global_evidence_vault()
    {
        $admin = User::factory()->create(['role' => 'Administrator']);

        $responseVault = $this->actingAs($admin)->get(route('admin.evidence.index'));
        $responseVault->assertSee('Global Evidence Vault and Storage Inspector');
    }

    public function test_admin_can_update_system_settings()
    {
        $admin = User::factory()->create(['role' => 'Administrator']);

        $responseSettings = $this->actingAs($admin)->post(route('admin.settings.update'), [
            'max_upload_size_mb' => 2048,
            'allowed_extensions' => 'raw,dd,e01,pcap,pdf,png',
            'session_timeout_minutes' => 60,
            'mandatory_2fa' => 1,
        ]);

        $responseSettings->assertRedirect();
        $this->assertDatabaseHas('system_settings', [
            'key' => 'max_upload_size_mb',
            'value' => '2048',
        ]);
        $this->assertDatabaseHas('system_settings', [
            'key' => 'mandatory_2fa',
            'value' => '1',
        ]);
    }
}
