<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // وثائق العطاء
        Schema::create('tender_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->constrained()->cascadeOnDelete();
            $table->enum('document_type', [
                'specifications', 'boq', 'drawings', 'instructions',
                'general_conditions', 'special_conditions', 'forms',
                'addendum', 'site_visit', 'questions_answers', 'other'
            ]);
            $table->string('file_name');
            $table->string('original_name');
            $table->string('file_path');
            $table->string('file_type', 50)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->integer('version')->default(1);
            $table->text('notes')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // شراء الوثائق
        Schema::create('tender_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->constrained()->cascadeOnDelete();
            $table->date('purchase_date');
            $table->string('receipt_number', 100);
            $table->decimal('amount', 10, 2);
            $table->enum('payment_method', ['cash', 'check', 'transfer'])->default('cash');
            $table->string('paid_by', 255);
            $table->string('receipt_image')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // بنود جدول الكميات BOQ
        Schema::create('boq_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('item_number', 50);
            $table->integer('level')->default(1);
            $table->text('description_ar');
            $table->text('description_en')->nullable();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->decimal('quantity', 18, 3)->default(0);
            $table->decimal('unit_rate', 18, 3)->nullable();
            $table->decimal('total_amount', 18, 3)->nullable();
            $table->decimal('material_cost', 18, 3)->nullable();
            $table->decimal('labor_cost', 18, 3)->nullable();
            $table->decimal('equipment_cost', 18, 3)->nullable();
            $table->decimal('subcontractor_cost', 18, 3)->nullable();
            $table->text('notes')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->foreign('parent_id')->references('id')->on('boq_items')->nullOnDelete();
            $table->index(['tender_id', 'item_number']);
        });

        // تسعير المواد
        Schema::create('boq_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('boq_item_id')->constrained()->cascadeOnDelete();
            $table->string('material_name');
            $table->text('description')->nullable();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->decimal('quantity_per_unit', 18, 6)->default(0);
            $table->decimal('wastage_percentage', 5, 2)->default(0);
            $table->decimal('total_quantity', 18, 3)->default(0);
            $table->decimal('unit_price', 18, 3)->default(0);
            $table->string('supplier_name')->nullable();
            $table->date('quote_date')->nullable();
            $table->decimal('total_cost', 18, 3)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // تسعير العمالة
        Schema::create('boq_labor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('boq_item_id')->constrained()->cascadeOnDelete();
            $table->string('labor_type');
            $table->text('description')->nullable();
            $table->enum('rate_unit', ['hour', 'day', 'unit'])->default('hour');
            $table->decimal('productivity', 18, 6)->default(1);
            $table->decimal('hours_per_unit', 18, 6)->default(0);
            $table->decimal('hourly_rate', 18, 3)->default(0);
            $table->decimal('total_cost', 18, 3)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // تسعير المعدات
        Schema::create('boq_equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('boq_item_id')->constrained()->cascadeOnDelete();
            $table->string('equipment_type');
            $table->text('description')->nullable();
            $table->enum('rate_unit', ['hour', 'day', 'month'])->default('hour');
            $table->decimal('productivity', 18, 6)->default(1);
            $table->decimal('hours_per_unit', 18, 6)->default(0);
            $table->decimal('hourly_rate', 18, 3)->default(0);
            $table->enum('ownership', ['owned', 'rented'])->default('owned');
            $table->decimal('total_cost', 18, 3)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // تسعير مقاولي الباطن
        Schema::create('boq_subcontractors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('boq_item_id')->constrained()->cascadeOnDelete();
            $table->string('subcontractor_name');
            $table->text('work_description')->nullable();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->decimal('quantity', 18, 3)->default(0);
            $table->decimal('unit_rate', 18, 3)->default(0);
            $table->string('quote_reference')->nullable();
            $table->date('quote_date')->nullable();
            $table->decimal('total_cost', 18, 3)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // المصاريف العامة
        Schema::create('tender_overheads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->constrained()->cascadeOnDelete();
            $table->string('category'); // site, admin, financial, other
            $table->string('description');
            $table->enum('calculation_type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('percentage', 5, 2)->nullable();
            $table->decimal('amount', 18, 3)->default(0);
            $table->text('notes')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tender_overheads');
        Schema::dropIfExists('boq_subcontractors');
        Schema::dropIfExists('boq_equipment');
        Schema::dropIfExists('boq_labor');
        Schema::dropIfExists('boq_materials');
        Schema::dropIfExists('boq_items');
        Schema::dropIfExists('tender_purchases');
        Schema::dropIfExists('tender_documents');
    }
};
