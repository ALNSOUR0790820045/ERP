<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // جدول الأوامر التغييرية
        Schema::create('contract_variations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            
            $table->string('vo_number', 50);
            $table->string('vo_type', 50);
            $table->string('title');
            $table->text('description');
            $table->text('reason')->nullable();
            
            $table->string('requested_by', 50); // employer, engineer, contractor
            $table->date('request_date');
            $table->string('instruction_reference')->nullable();
            $table->text('drawings_affected')->nullable();
            
            $table->decimal('submitted_amount', 18, 3)->default(0);
            $table->decimal('approved_amount', 18, 3)->default(0);
            $table->integer('time_extension_days')->default(0);
            
            $table->string('status', 50)->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->date('approval_date')->nullable();
            $table->string('approval_reference')->nullable();
            $table->text('approval_notes')->nullable();
            
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            
            $table->unique(['contract_id', 'vo_number']);
        });

        // بنود الأوامر التغييرية
        Schema::create('contract_variation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variation_id')->constrained('contract_variations')->cascadeOnDelete();
            $table->foreignId('contract_item_id')->nullable()->constrained('contract_items');
            
            $table->string('item_number', 50);
            $table->text('description');
            $table->foreignId('unit_id')->nullable()->constrained('units');
            $table->string('unit_code', 20)->nullable();
            
            $table->decimal('quantity', 18, 6)->default(0);
            $table->string('pricing_method', 50); // contract_rate, derived, new, daywork
            $table->decimal('unit_rate', 18, 3)->default(0);
            $table->decimal('total_amount', 18, 3)->default(0);
            
            $table->boolean('is_addition')->default(true);
            $table->text('justification')->nullable();
            
            $table->timestamps();
        });

        // جدول الضمانات
        Schema::create('contract_bonds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            
            $table->string('bond_type', 50); // performance, advance, retention, tender
            $table->string('bond_number')->nullable();
            $table->string('issuer')->nullable(); // Bank name
            $table->decimal('amount', 18, 3);
            $table->foreignId('currency_id')->nullable()->constrained('currencies');
            
            $table->date('issue_date')->nullable();
            $table->date('validity_date')->nullable();
            $table->date('expiry_date')->nullable();
            
            $table->string('status', 50)->default('active'); // active, expired, released, claimed
            $table->date('release_date')->nullable();
            $table->text('notes')->nullable();
            
            $table->string('document_path')->nullable();
            
            $table->timestamps();
        });

        // جدول التمديدات الزمنية
        Schema::create('contract_extensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variation_id')->nullable()->constrained('contract_variations');
            
            $table->string('extension_number', 50);
            $table->string('reason');
            $table->text('description')->nullable();
            
            $table->integer('requested_days');
            $table->integer('approved_days')->nullable();
            $table->date('new_completion_date')->nullable();
            
            $table->string('status', 50)->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->date('approval_date')->nullable();
            
            $table->timestamps();
        });

        // سجل أحداث العقد
        Schema::create('contract_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            
            $table->string('event_type', 50);
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('event_date');
            $table->json('details')->nullable();
            
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_events');
        Schema::dropIfExists('contract_extensions');
        Schema::dropIfExists('contract_bonds');
        Schema::dropIfExists('contract_variation_items');
        Schema::dropIfExists('contract_variations');
    }
};
