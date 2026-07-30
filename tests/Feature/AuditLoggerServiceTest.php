<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AuditLoggerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLoggerServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_logs_are_hash_chained_and_tamper_evident()
    {
        $user = User::factory()->create(['role' => 'Investigator']);
        $this->actingAs($user);

        // 1. Log 3 actions
        $log1 = AuditLoggerService::log('create_case', 'Case', 1, ['title' => 'Case Alpha']);
        $log2 = AuditLoggerService::log('upload_evidence', 'EvidenceItem', 1, ['file' => 'dump.raw']);
        $log3 = AuditLoggerService::log('transfer_custody', 'CustodyTransfer', 1, ['to' => 'Agent Smith']);

        // 2. Verify chain integrity initially
        $result = AuditLoggerService::verifyChainIntegrity();
        $this->assertTrue($result['is_valid']);
        $this->assertEquals(3, $result['total_entries']);

        // 3. Simulate unauthorized database modification (tampering with log 2)
        $log2->update(['details_json' => ['file' => 'TAMPERED.raw']]);

        // 4. Verify chain integrity detects tampering
        $tamperedResult = AuditLoggerService::verifyChainIntegrity();
        $this->assertFalse($tamperedResult['is_valid']);
        $this->assertGreaterThan(0, count($tamperedResult['tampered_entries']));
    }
}
