<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Channel partners give their Aadhaar number instead of GSTIN / trade licence.
 * Guarded, so it is safe to re-run.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('channel_partners') && !Schema::hasColumn('channel_partners', 'aadhaar_number')) {
            Schema::table('channel_partners', function (Blueprint $table) {
                $table->string('aadhaar_number', 12)->nullable()->after('pan_number');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('channel_partners', 'aadhaar_number')) {
            Schema::table('channel_partners', function (Blueprint $table) {
                $table->dropColumn('aadhaar_number');
            });
        }
    }
};
