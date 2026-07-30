<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evidence_items', function (Blueprint $table) {
            $table->string('custom_classification')->nullable()->after('classification');
        });
    }

    public function down(): void
    {
        Schema::table('evidence_items', function (Blueprint $table) {
            $table->dropColumn('custom_classification');
        });
    }
};
