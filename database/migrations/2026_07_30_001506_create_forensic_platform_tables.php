<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Cases table
        Schema::create('cases', function (Blueprint $table) {
            $table->id();
            $table->string('case_number')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['open', 'closed', 'archived'])->default('open');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        // 2. Case Assignments
        Schema::create('case_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('role_in_case')->default('Investigator');
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamps();

            $table->unique(['case_id', 'user_id']);
        });

        // 3. Evidence Items
        Schema::create('evidence_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->onDelete('cascade');
            $table->string('evidence_number')->unique();
            $table->text('description');
            $table->string('source_device');
            $table->enum('classification', ['original', 'forensic_copy', 'export', 'screenshot', 'reconstructed'])->default('original');
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_hash_sha256');
            $table->unsignedBigInteger('file_size');
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamp('collected_at');
            $table->string('collected_location');
            $table->foreignId('current_custodian_id')->constrained('users');
            $table->timestamps();
        });

        // 4. Custody Transfers
        Schema::create('custody_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evidence_item_id')->constrained('evidence_items')->onDelete('cascade');
            $table->foreignId('from_user_id')->constrained('users');
            $table->foreignId('to_user_id')->constrained('users');
            $table->text('reason');
            $table->timestamp('transferred_at')->useCurrent();
            $table->timestamp('accepted_at')->nullable();
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->timestamps();
        });

        // 5. Audit Log (Insert-Only Hash Chain)
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('action_type'); // upload, view, download, transfer, export, login, etc.
            $table->string('target_type')->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->json('details_json')->nullable();
            $table->string('entry_hash');
            $table->string('previous_entry_hash')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // 6. Generated Reports
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->onDelete('cascade');
            $table->foreignId('evidence_item_id')->nullable()->constrained('evidence_items')->onDelete('cascade');
            $table->string('type'); // coc_form, case_summary
            $table->string('file_path');
            $table->foreignId('generated_by')->constrained('users');
            $table->timestamp('generated_at')->useCurrent();
            $table->string('manifest_hash');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('custody_transfers');
        Schema::dropIfExists('evidence_items');
        Schema::dropIfExists('case_assignments');
        Schema::dropIfExists('cases');
    }
};
