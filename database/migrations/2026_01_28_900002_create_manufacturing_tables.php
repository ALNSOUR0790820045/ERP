<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // قائمة المواد (BOM) - Bill of Materials
        Schema::create('bill_of_materials', function (Blueprint $table) {
            $table->id();
            $table->string('bom_number', 50)->unique();
            $table->string('product_name');
            $table->string('product_name_en')->nullable();
            $table->string('unit')->default('unit');
            $table->decimal('quantity', 18, 3)->default(1);
            $table->integer('version')->default(1);
            $table->date('effective_date');
            $table->enum('status', ['draft', 'active', 'inactive'])->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // بنود قائمة المواد
        Schema::create('bom_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bom_id')->constrained('bill_of_materials')->cascadeOnDelete();
            $table->foreignId('material_id')->nullable()->constrained('materials')->nullOnDelete();
            $table->string('item_name')->nullable();
            $table->decimal('quantity', 18, 4);
            $table->string('unit');
            $table->decimal('wastage_percent', 5, 2)->default(0);
            $table->decimal('net_quantity', 18, 4)->nullable();
            $table->boolean('is_optional')->default(false);
            $table->foreignId('substitute_id')->nullable()->constrained('materials')->nullOnDelete();
            $table->integer('sequence')->default(0);
            $table->timestamps();
        });

        // أوامر الإنتاج
        Schema::create('production_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 50)->unique();
            $table->date('order_date');
            $table->foreignId('bom_id')->constrained('bill_of_materials')->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->decimal('planned_quantity', 18, 3);
            $table->decimal('produced_quantity', 18, 3)->default(0);
            $table->decimal('rejected_quantity', 18, 3)->default(0);
            $table->dateTime('planned_start')->nullable();
            $table->dateTime('planned_end')->nullable();
            $table->dateTime('actual_start')->nullable();
            $table->dateTime('actual_end')->nullable();
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->enum('status', ['draft', 'released', 'in_progress', 'completed', 'cancelled', 'on_hold'])->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // بنود أمر الإنتاج - المواد المستهلكة
        Schema::create('production_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained('production_orders')->cascadeOnDelete();
            $table->foreignId('material_id')->nullable()->constrained('materials')->nullOnDelete();
            $table->string('item_name');
            $table->decimal('planned_quantity', 18, 4);
            $table->decimal('actual_quantity', 18, 4)->default(0);
            $table->string('unit');
            $table->timestamps();
        });

        // خلطات الخرسانة
        Schema::create('concrete_mix_designs', function (Blueprint $table) {
            $table->id();
            $table->string('mix_code', 50)->unique();
            $table->string('mix_name');
            $table->string('grade'); // B250, B300, B350, etc.
            $table->decimal('target_slump', 8, 2)->nullable(); // mm
            $table->decimal('water_cement_ratio', 5, 3)->nullable();
            $table->json('components')->nullable(); // cement, sand, aggregate, water, additives
            $table->decimal('standard_cost', 15, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // دفعات الخرسانة
        Schema::create('concrete_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_number', 50)->unique();
            $table->dateTime('production_date');
            $table->foreignId('mix_design_id')->constrained('concrete_mix_designs')->cascadeOnDelete();
            $table->foreignId('production_order_id')->nullable()->constrained('production_orders')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->string('truck_number')->nullable();
            $table->string('driver_name')->nullable();
            $table->string('pour_location')->nullable();
            $table->decimal('volume', 10, 3); // m³
            $table->decimal('slump', 8, 2)->nullable(); // mm
            $table->decimal('temperature', 5, 2)->nullable(); // °C
            $table->integer('samples_taken')->default(0);
            $table->enum('status', ['mixing', 'in_transit', 'poured', 'rejected'])->default('mixing');
            $table->text('notes')->nullable();
            $table->foreignId('produced_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // اختبارات الخرسانة
        Schema::create('concrete_tests', function (Blueprint $table) {
            $table->id();
            $table->string('test_number', 50)->unique();
            $table->foreignId('batch_id')->constrained('concrete_batches')->cascadeOnDelete();
            $table->date('casting_date');
            $table->integer('test_age')->default(28); // days
            $table->date('test_date');
            $table->decimal('target_strength', 10, 2)->nullable(); // MPa
            $table->decimal('actual_strength', 10, 2)->nullable(); // MPa
            $table->enum('result', ['pending', 'pass', 'fail'])->default('pending');
            $table->string('tested_by')->nullable();
            $table->string('lab_name')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // أوامر تصنيع الحديد
        Schema::create('steel_fabrication_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 50)->unique();
            $table->date('order_date');
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->string('element_type')->nullable(); // Column, Beam, Slab, Foundation
            $table->string('drawing_number')->nullable();
            $table->decimal('total_weight', 15, 3)->nullable(); // kg
            $table->date('required_date')->nullable();
            $table->date('delivery_date')->nullable();
            $table->enum('status', ['draft', 'approved', 'in_progress', 'completed', 'delivered', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // جدول تفريد الحديد (Bar Schedule)
        Schema::create('bar_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('steel_order_id')->constrained('steel_fabrication_orders')->cascadeOnDelete();
            $table->string('mark'); // A1, B1, C1
            $table->integer('diameter'); // mm (8, 10, 12, 14, 16, 20, 25, 32)
            $table->string('shape')->nullable(); // Straight, L-Shape, U-Shape
            $table->decimal('length', 10, 3); // m
            $table->integer('count');
            $table->decimal('unit_weight', 10, 4)->nullable(); // kg/m
            $table->decimal('total_weight', 15, 3)->nullable(); // kg
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // ورش التصنيع
        Schema::create('workshops', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->enum('type', ['concrete', 'steel', 'carpentry', 'metalwork', 'other']);
            $table->string('location')->nullable();
            $table->decimal('capacity', 15, 2)->nullable();
            $table->string('capacity_unit')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // سجل الإنتاج اليومي
        Schema::create('daily_production_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workshop_id')->constrained('workshops')->cascadeOnDelete();
            $table->date('log_date');
            $table->string('shift')->nullable(); // Morning, Evening, Night
            $table->foreignId('production_order_id')->nullable()->constrained('production_orders')->nullOnDelete();
            $table->decimal('quantity_produced', 18, 3);
            $table->string('unit');
            $table->integer('workers_count')->nullable();
            $table->decimal('hours_worked', 8, 2)->nullable();
            $table->text('activities')->nullable();
            $table->text('issues')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->unique(['workshop_id', 'log_date', 'shift']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_production_logs');
        Schema::dropIfExists('workshops');
        Schema::dropIfExists('bar_schedules');
        Schema::dropIfExists('steel_fabrication_orders');
        Schema::dropIfExists('concrete_tests');
        Schema::dropIfExists('concrete_batches');
        Schema::dropIfExists('concrete_mix_designs');
        Schema::dropIfExists('production_order_items');
        Schema::dropIfExists('production_orders');
        Schema::dropIfExists('bom_items');
        Schema::dropIfExists('bill_of_materials');
    }
};
