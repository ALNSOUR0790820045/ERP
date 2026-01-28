<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // فئات المعدات
        Schema::create('equipment_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('equipment_categories');
            $table->string('code', 20)->unique();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // المعدات
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('equipment_categories');
            $table->foreignId('company_id')->nullable()->constrained();
            
            $table->string('code', 50)->unique();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->text('description')->nullable();
            
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number', 100)->nullable();
            $table->integer('year_manufactured')->nullable();
            
            $table->string('ownership_type', 30)->default('owned'); // owned, rented, leased
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_price', 18, 3)->nullable();
            $table->decimal('current_value', 18, 3)->nullable();
            $table->decimal('depreciation_rate', 5, 2)->nullable();
            
            $table->decimal('hourly_rate', 12, 3)->nullable();
            $table->decimal('daily_rate', 12, 3)->nullable();
            $table->decimal('monthly_rate', 12, 3)->nullable();
            
            $table->decimal('fuel_consumption', 10, 2)->nullable();
            $table->string('fuel_type', 30)->nullable();
            $table->decimal('capacity', 10, 2)->nullable();
            $table->string('capacity_unit', 20)->nullable();
            
            $table->string('status', 30)->default('available'); // available, assigned, maintenance, breakdown, disposed
            $table->foreignId('current_project_id')->nullable()->constrained('projects');
            $table->foreignId('current_operator_id')->nullable();
            
            $table->decimal('odometer', 15, 2)->nullable();
            $table->decimal('hour_meter', 15, 2)->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // تعيين المعدات للمشاريع
        Schema::create('equipment_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained();
            
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('assignment_type', 30)->default('full_time');
            
            $table->decimal('agreed_rate', 12, 3)->nullable();
            $table->string('rate_type', 20)->nullable();
            
            $table->foreignId('assigned_by')->nullable()->constrained('users');
            $table->text('notes')->nullable();
            
            $table->timestamps();
        });

        // سجل تشغيل المعدات
        Schema::create('equipment_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained();
            $table->foreignId('daily_report_id')->nullable()->constrained('project_daily_reports');
            
            $table->date('usage_date');
            $table->decimal('working_hours', 6, 2)->default(0);
            $table->decimal('idle_hours', 6, 2)->default(0);
            $table->decimal('fuel_consumed', 10, 2)->nullable();
            
            $table->decimal('odometer_start', 15, 2)->nullable();
            $table->decimal('odometer_end', 15, 2)->nullable();
            $table->decimal('hour_meter_start', 15, 2)->nullable();
            $table->decimal('hour_meter_end', 15, 2)->nullable();
            
            $table->string('operator_name')->nullable();
            $table->text('work_description')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
        });

        // صيانة المعدات
        Schema::create('equipment_maintenance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained()->cascadeOnDelete();
            
            $table->string('maintenance_number', 50);
            $table->string('maintenance_type', 30); // preventive, corrective, breakdown
            $table->date('scheduled_date')->nullable();
            $table->date('actual_date')->nullable();
            $table->date('completion_date')->nullable();
            
            $table->text('description');
            $table->text('work_done')->nullable();
            $table->text('parts_replaced')->nullable();
            
            $table->decimal('labor_cost', 12, 3)->default(0);
            $table->decimal('parts_cost', 12, 3)->default(0);
            $table->decimal('total_cost', 12, 3)->default(0);
            
            $table->string('status', 30)->default('scheduled');
            $table->string('service_provider')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users');
            
            $table->timestamps();
        });

        // تزويد الوقود
        Schema::create('equipment_fuel_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained();
            
            $table->date('fuel_date');
            $table->string('fuel_type', 30);
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price', 10, 3);
            $table->decimal('total_cost', 12, 3);
            
            $table->decimal('odometer_reading', 15, 2)->nullable();
            $table->decimal('hour_meter_reading', 15, 2)->nullable();
            
            $table->string('fuel_station')->nullable();
            $table->string('receipt_number', 50)->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_fuel_logs');
        Schema::dropIfExists('equipment_maintenance');
        Schema::dropIfExists('equipment_usage_logs');
        Schema::dropIfExists('equipment_assignments');
        Schema::dropIfExists('equipment');
        Schema::dropIfExists('equipment_categories');
    }
};
