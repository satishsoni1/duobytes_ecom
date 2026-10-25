<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Channel partner program.
 *
 * The base schema comes from shop.sql + sqlupdates/*.sql; this is the only
 * Laravel migration. Every step is guarded, so it is safe to re-run:
 *
 *   php artisan migrate --force
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('channel_partners')) {
            Schema::create('channel_partners', function (Blueprint $table) {
                $table->id();
                // users.id is INT UNSIGNED in this schema
                $table->unsignedInteger('user_id')->unique();
                $table->string('partner_code', 20)->nullable()->unique();

                $table->string('company_name')->nullable();
                $table->string('business_type', 40)->nullable();
                $table->string('contact_person')->nullable();
                $table->string('designation', 100)->nullable();
                $table->string('gst_number', 20)->nullable();
                $table->string('pan_number', 20)->nullable();
                $table->string('registration_number', 50)->nullable();
                $table->string('address_line', 500)->nullable();
                $table->string('city', 100)->nullable();
                $table->string('state', 100)->nullable();
                $table->string('postal_code', 20)->nullable();
                $table->string('country', 100)->nullable();
                $table->string('website')->nullable();
                $table->string('expected_monthly_volume', 50)->nullable();

                // draft -> submitted -> approved | rejected ; approved <-> suspended
                $table->string('status', 20)->default('draft')->index();
                $table->text('rejection_reason')->nullable();

                // Contract terms (agreed offline, entered by admin)
                $table->decimal('default_discount', 5, 2)->default(0);
                $table->decimal('credit_limit', 14, 2)->nullable();
                $table->string('payment_terms', 100)->nullable();
                $table->string('contract_reference', 100)->nullable();
                $table->date('contract_valid_until')->nullable();
                $table->decimal('min_order_value', 14, 2)->default(0);
                $table->text('admin_notes')->nullable();

                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->unsignedInteger('approved_by')->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('channel_partner_documents')) {
            Schema::create('channel_partner_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('channel_partner_id')->constrained('channel_partners')->cascadeOnDelete();
                $table->string('document_type', 50);
                $table->string('original_name');
                $table->string('path');
                $table->string('mime_type', 100)->nullable();
                $table->unsignedInteger('size')->default(0);
                $table->string('status', 20)->default('pending'); // pending | verified | rejected
                $table->string('remarks', 500)->nullable();
                $table->timestamps();

                $table->index(['channel_partner_id', 'document_type']);
            });
        }

        if (!Schema::hasTable('channel_partner_price_rules')) {
            Schema::create('channel_partner_price_rules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('channel_partner_id')->constrained('channel_partners')->cascadeOnDelete();
                // Scope: product-specific, category-wide, or (both null) all products
                $table->integer('product_id')->nullable();
                $table->integer('category_id')->nullable();
                $table->unsignedInteger('min_qty')->default(1);
                $table->string('discount_type', 20); // percent | amount | fixed_price
                $table->decimal('discount_value', 14, 2);
                $table->date('starts_at')->nullable();
                $table->date('ends_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->string('note')->nullable();
                $table->timestamps();

                $table->index(['channel_partner_id', 'product_id']);
                $table->index(['channel_partner_id', 'category_id']);
            });
        }

        if (!Schema::hasTable('channel_partner_cart_items')) {
            Schema::create('channel_partner_cart_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('channel_partner_id')->constrained('channel_partners')->cascadeOnDelete();
                $table->integer('product_id');
                $table->string('variation', 191)->default('');
                $table->unsignedInteger('quantity');
                $table->timestamps();

                $table->unique(['channel_partner_id', 'product_id', 'variation'], 'cp_cart_unique_line');
            });
        }

        if (!Schema::hasTable('channel_partner_orders')) {
            Schema::create('channel_partner_orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('channel_partner_id')->constrained('channel_partners')->cascadeOnDelete();
                $table->integer('combined_order_id')->index();
                $table->integer('order_id')->index();
                $table->string('po_number', 100)->nullable();
                $table->decimal('list_total', 14, 2)->default(0);
                $table->decimal('discount_total', 14, 2)->default(0);
                $table->decimal('net_total', 14, 2)->default(0);
                // Per-line price breakdown at the time of ordering (audit trail)
                $table->longText('pricing_snapshot')->nullable();
                $table->timestamps();
            });
        }

        $now = now();
        foreach (['view_channel_partners', 'manage_channel_partners', 'manage_channel_partner_pricing'] as $permission) {
            if (!DB::table('permissions')->where('name', $permission)->exists()) {
                DB::table('permissions')->insert([
                    'name' => $permission,
                    'section' => 'channel_partner',
                    'guard_name' => 'web',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
        app()['cache']->forget('spatie.permission.cache');
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_partner_orders');
        Schema::dropIfExists('channel_partner_cart_items');
        Schema::dropIfExists('channel_partner_price_rules');
        Schema::dropIfExists('channel_partner_documents');
        Schema::dropIfExists('channel_partners');
        DB::table('permissions')->where('section', 'channel_partner')->delete();
    }
};
