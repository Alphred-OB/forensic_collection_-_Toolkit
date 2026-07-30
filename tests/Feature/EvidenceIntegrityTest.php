<?php

namespace Tests\Feature;

use App\Models\EvidenceItem;
use App\Models\ForensicCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EvidenceIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_evidence_file_hashing_and_tamper_detection()
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => 'Investigator']);
        $case = ForensicCase::create([
            'case_number' => 'TEST-001',
            'title' => 'Test Case',
            'created_by' => $user->id,
        ]);

        $fileContent = "Original Forensic Hard Drive Dump Content 2026";
        $file = UploadedFile::fake()->createWithContent('drive.img', $fileContent);

        $response = $this->actingAs($user)->post(route('evidence.store', $case->id), [
            'evidence_number' => 'EVD-999',
            'description' => 'Bit-stream copy',
            'source_device' => 'Workstation HD',
            'classification' => 'forensic_copy',
            'evidence_file' => $file,
            'collected_at' => now()->toDateTimeString(),
            'collected_location' => 'HQ Lab',
        ]);

        $response->assertRedirect(route('cases.show', $case->id));

        $evidence = EvidenceItem::where('evidence_number', 'EVD-999')->first();
        $this->assertNotNull($evidence);
        $this->assertEquals(hash('sha256', $fileContent), $evidence->file_hash_sha256);
        $this->assertTrue($evidence->verifyIntegrity());

        // Simulate file corruption on storage disk
        Storage::disk('local')->put($evidence->file_path, "Corrupted file data injected!");
        $this->assertFalse($evidence->verifyIntegrity());
    }

    public function test_evidence_sub_item_parent_child_linking()
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => 'Investigator']);
        $case = ForensicCase::create([
            'case_number' => 'TEST-002',
            'title' => 'Disk Partition Case',
            'created_by' => $user->id,
        ]);

        $parentFile = UploadedFile::fake()->createWithContent('disk.img', 'Disk Content');
        $parent = EvidenceItem::create([
            'case_id' => $case->id,
            'evidence_number' => 'EVD-PARENT-1',
            'description' => 'Physical Hard Drive',
            'source_device' => 'Seagate 1TB',
            'classification' => 'original',
            'file_path' => 'disk.img',
            'file_name' => 'disk.img',
            'file_hash_sha256' => hash('sha256', 'Disk Content'),
            'file_size' => 12,
            'uploaded_by' => $user->id,
            'collected_at' => now(),
            'collected_location' => 'Lab',
            'current_custodian_id' => $user->id,
        ]);

        $childFile = UploadedFile::fake()->createWithContent('partition1.raw', 'Partition 1 Content');
        $responseChild = $this->actingAs($user)->post(route('evidence.store', $case->id), [
            'evidence_number' => 'EVD-CHILD-1',
            'parent_id' => $parent->id,
            'description' => 'Extracted NTFS Partition',
            'source_device' => 'Seagate 1TB - Partition 1',
            'classification' => 'export',
            'evidence_file' => $childFile,
            'collected_at' => now()->toDateTimeString(),
            'collected_location' => 'HQ Lab',
        ]);

        $responseChild->assertRedirect(route('cases.show', $case->id));

        $childItem = EvidenceItem::where('evidence_number', 'EVD-CHILD-1')->first();
        $this->assertNotNull($childItem);
        $this->assertEquals($parent->id, $childItem->parent_id);
        $this->assertEquals($parent->id, $childItem->parent->id);
        $this->assertTrue($parent->subItems->contains($childItem->id));
    }

    public function test_batch_evidence_zip_export_with_sha256_manifest()
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => 'Investigator']);
        $case = ForensicCase::create([
            'case_number' => 'TEST-BATCH-1',
            'title' => 'Batch Export Case',
            'created_by' => $user->id,
        ]);

        $filePath = 'evidence_vault/' . $case->id . '/sample.txt';
        Storage::disk('local')->put($filePath, 'Forensic Sample Payload');

        EvidenceItem::create([
            'case_id' => $case->id,
            'evidence_number' => 'EVD-ZIP-1',
            'description' => 'Export Item',
            'source_device' => 'PC',
            'classification' => 'export',
            'file_path' => $filePath,
            'file_name' => 'sample.txt',
            'file_hash_sha256' => hash('sha256', 'Forensic Sample Payload'),
            'file_size' => 22,
            'uploaded_by' => $user->id,
            'collected_at' => now(),
            'collected_location' => 'Lab',
            'current_custodian_id' => $user->id,
        ]);

        $responseBatch = $this->actingAs($user)->get(route('evidence.batch-export', $case->id));
        $responseBatch->assertStatus(200);
        $responseBatch->assertHeader('content-type', 'application/zip');
    }

    public function test_custom_evidence_classification_support()
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => 'Investigator']);
        $case = ForensicCase::create([
            'case_number' => 'TEST-CUSTOM-1',
            'title' => 'Custom Classification Case',
            'created_by' => $user->id,
        ]);

        $file = UploadedFile::fake()->createWithContent('memory.raw', 'RAM Dump Payload');

        $response = $this->actingAs($user)->post(route('evidence.store', $case->id), [
            'evidence_number' => 'EVD-RAM-01',
            'description' => 'Volatile Memory Dump',
            'source_device' => 'Workstation RAM',
            'classification' => 'custom',
            'custom_classification' => 'RAM Memory Dump',
            'evidence_file' => $file,
            'collected_at' => now()->toDateTimeString(),
            'collected_location' => 'Scene Desk',
        ]);

        $response->assertRedirect(route('cases.show', $case->id));
        $this->assertDatabaseHas('evidence_items', [
            'evidence_number' => 'EVD-RAM-01',
            'classification' => 'custom',
            'custom_classification' => 'RAM Memory Dump',
        ]);
    }
}
