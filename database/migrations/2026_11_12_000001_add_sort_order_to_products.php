<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin-set display order for products on the storefront.
 * Lower numbers show first; NULL (no order set) falls back to newest first.
 * Guarded, so it is safe to re-run.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('products') && !Schema::hasColumn('products', 'sort_order')) {
            Schema::table('products', function (Blueprint $table) {
                $table->unsignedInteger('sort_order')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'sort_order')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropIndex(['sort_order']);
                $table->dropColumn('sort_order');
            });
        }
    }
};
