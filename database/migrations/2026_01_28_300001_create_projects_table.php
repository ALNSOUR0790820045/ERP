<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // جدول المشاريع الرئيسي
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->nullable()->constrained();
            $table->foreignId('company_id')->nullable()->constrained();
            $table->foreignId('branch_id')->nullable()->constrained();
            
            // البيانات الأساسية
            $table->string('project_number', 50)->unique();
            $table->string('code', 20)->unique();
            $table->string('name_ar', 500);
            $table->string('name_en', 500)->nullable();
            $table->text('description')->nullable();
            $table->foreignId('project_type_id')->nullable()->constrained('project_types');
            $table->string('priority', 20)->default('medium');
            
            // العميل والموقع
            $table->foreignId('customer_id')->nullable()->constrained('owners');
            $table->foreignId('consultant_id')->nullable()->constrained('consultants');
            $table->foreignId('country_id')->nullable()->constrained('countries');
            $table->foreignId('city_id')->nullable()->constrained('cities');
            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->decimal('site_area', 15, 2)->nullable();
            $table->decimal('building_area', 15, 2)->nullable();
            
            // التواريخ
            $table->date('planned_start_date')->nullable();
            $table->date('planned_end_date')->nullable();
            $table->date('actual_start_date')->nullable();
            $table->date('actual_end_date')->nullable();
            $table->integer('duration_days')->nullable();
            $table->integer('working_days_per_week')->default(6);
            $table->decimal('working_hours_per_day', 4, 2)->default(8);
            
            // القيم المالية
            $table->decimal('contract_value', 18, 3)->default(0);
            $table->decimal('budget', 18, 3)->default(0);
            $table->decimal('actual_cost', 18, 3)->default(0);
            $table->foreignId('currency_id')->nullable()->constrained('currencies');
            
            // فريق المشروع
            $table->foreignId('project_manager_id')->nullable()->constrained('users');
            $table->foreignId('site_engineer_id')->nullable()->constrained('users');
            $table->foreignId('safety_officer_id')->nullable()->constrained('users');
            $table->foreignId('quality_officer_id')->nullable()->constrained('users');
            $table->foreignId('accountant_id')->nullable()->constrained('users');
            
            // التقدم والحالة
            $table->decimal('planned_progress', 5, 2)->default(0);
            $table->decimal('actual_progress', 5, 2)->default(0);
            $table->string('status', 30)->default('planning');
            
            // EVM مؤشرات
            $table->decimal('pv', 18, 3)->nullable(); // Planned Value
            $table->decimal('ev', 18, 3)->nullable(); // Earned Value
            $table->decimal('ac', 18, 3)->nullable(); // Actual Cost
            $table->decimal('spi', 6, 4)->nullable(); // Schedule Performance Index
            $table->decimal('cpi', 6, 4)->nullable(); // Cost Performance Index
            
            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        // جدول هيكل تقسيم العمل WBS
        Schema::create('project_wbs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('project_wbs');
            
            $table->string('wbs_code', 50);
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->text('description')->nullable();
            $table->string('type', 20); // phase, activity, task, milestone
            $table->integer('level')->default(1);
            
            // التواريخ
            $table->date('planned_start')->nullable();
            $table->date('planned_finish')->nullable();
            $table->date('actual_start')->nullable();
            $table->date('actual_finish')->nullable();
            $table->integer('duration_days')->nullable();
            
            // التقدم
            $table->decimal('planned_progress', 5, 2)->default(0);
            $table->decimal('actual_progress', 5, 2)->default(0);
            $table->decimal('weight', 5, 4)->nullable();
            
            // التكلفة
            $table->decimal('budget', 18, 3)->default(0);
            $table->decimal('actual_cost', 18, 3)->default(0);
            
            // CPM
            $table->integer('early_start')->nullable();
            $table->integer('early_finish')->nullable();
            $table->integer('late_start')->nullable();
            $table->integer('late_finish')->nullable();
            $table->integer('total_float')->nullable();
            $table->integer('free_float')->nullable();
            $table->boolean('is_critical')->default(false);
            
            // القيود
            $table->string('constraint_type', 30)->nullable();
            $table->date('constraint_date')->nullable();
            
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->unique(['project_id', 'wbs_code']);
        });

        // العلاقات بين الأنشطة
        Schema::create('project_wbs_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('predecessor_id')->constrained('project_wbs')->cascadeOnDelete();
            $table->foreignId('successor_id')->constrained('project_wbs')->cascadeOnDelete();
            $table->string('type', 10)->default('FS'); // FS, SS, FF, SF
            $table->integer('lag_days')->default(0);
            $table->timestamps();
            
            $table->unique(['predecessor_id', 'successor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_wbs_dependencies');
        Schema::dropIfExists('project_wbs');
        Schema::dropIfExists('projects');
    }
};
