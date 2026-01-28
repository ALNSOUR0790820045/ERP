<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // التقارير اليومية
        Schema::create('project_daily_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            
            $table->string('report_number', 50);
            $table->date('report_date');
            
            // الطقس
            $table->string('weather', 30)->nullable();
            $table->integer('temperature_min')->nullable();
            $table->integer('temperature_max')->nullable();
            
            // ساعات العمل
            $table->decimal('working_hours', 4, 2)->default(8);
            $table->string('shift', 20)->default('day'); // day, night, both
            $table->boolean('is_working_day')->default(true);
            $table->string('non_working_reason')->nullable();
            
            // ملخص
            $table->text('summary')->nullable();
            $table->text('problems')->nullable();
            $table->text('solutions')->nullable();
            $table->text('tomorrow_plan')->nullable();
            
            // الموافقات
            $table->string('status', 30)->default('draft');
            $table->foreignId('submitted_by')->nullable()->constrained('users');
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            
            $table->unique(['project_id', 'report_date']);
        });

        // العمالة في التقرير اليومي
        Schema::create('project_daily_labor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_report_id')->constrained('project_daily_reports')->cascadeOnDelete();
            
            $table->string('labor_type', 50);
            $table->string('trade', 100)->nullable();
            $table->integer('count')->default(0);
            $table->decimal('hours', 5, 2)->default(0);
            $table->decimal('overtime_hours', 5, 2)->default(0);
            $table->foreignId('wbs_id')->nullable()->constrained('project_wbs');
            $table->text('activity_description')->nullable();
            
            $table->timestamps();
        });

        // المعدات في التقرير اليومي
        Schema::create('project_daily_equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_report_id')->constrained('project_daily_reports')->cascadeOnDelete();
            
            $table->string('equipment_name');
            $table->string('equipment_code', 50)->nullable();
            $table->string('status', 30); // working, idle, maintenance, breakdown
            $table->decimal('working_hours', 5, 2)->default(0);
            $table->decimal('idle_hours', 5, 2)->default(0);
            $table->decimal('fuel_consumption', 10, 2)->nullable();
            $table->foreignId('wbs_id')->nullable()->constrained('project_wbs');
            $table->text('notes')->nullable();
            
            $table->timestamps();
        });

        // المواد الواردة في التقرير اليومي
        Schema::create('project_daily_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_report_id')->constrained('project_daily_reports')->cascadeOnDelete();
            
            $table->string('material_name');
            $table->string('material_code', 50)->nullable();
            $table->decimal('quantity', 15, 4)->default(0);
            $table->foreignId('unit_id')->nullable()->constrained('units');
            $table->string('supplier')->nullable();
            $table->string('delivery_note', 100)->nullable();
            $table->string('status', 30)->default('accepted'); // accepted, rejected, pending
            $table->text('notes')->nullable();
            
            $table->timestamps();
        });

        // الأعمال المنجزة في التقرير اليومي
        Schema::create('project_daily_works', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_report_id')->constrained('project_daily_reports')->cascadeOnDelete();
            $table->foreignId('wbs_id')->nullable()->constrained('project_wbs');
            $table->foreignId('contract_item_id')->nullable()->constrained('contract_items');
            
            $table->text('description');
            $table->string('location')->nullable();
            $table->decimal('quantity', 15, 4)->default(0);
            $table->foreignId('unit_id')->nullable()->constrained('units');
            $table->decimal('progress_percentage', 5, 2)->nullable();
            
            $table->timestamps();
        });

        // أحداث وملاحظات التقرير اليومي
        Schema::create('project_daily_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_report_id')->constrained('project_daily_reports')->cascadeOnDelete();
            
            $table->string('event_type', 50); // instruction, visitor, incident, delay, other
            $table->time('event_time')->nullable();
            $table->string('title');
            $table->text('description');
            $table->string('persons_involved')->nullable();
            
            $table->timestamps();
        });

        // صور التقرير اليومي
        Schema::create('project_daily_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_report_id')->constrained('project_daily_reports')->cascadeOnDelete();
            
            $table->string('file_path');
            $table->string('caption')->nullable();
            $table->string('location')->nullable();
            $table->foreignId('wbs_id')->nullable()->constrained('project_wbs');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_daily_photos');
        Schema::dropIfExists('project_daily_events');
        Schema::dropIfExists('project_daily_works');
        Schema::dropIfExists('project_daily_materials');
        Schema::dropIfExists('project_daily_equipment');
        Schema::dropIfExists('project_daily_labor');
        Schema::dropIfExists('project_daily_reports');
    }
};
