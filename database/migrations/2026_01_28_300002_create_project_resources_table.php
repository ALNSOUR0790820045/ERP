<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // موارد المشروع
        if (!Schema::hasTable('project_resources')) {
        Schema::create('project_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            
            $table->string('resource_type', 30); // labor, equipment, material, subcontractor
            $table->string('name');
            $table->string('code', 50)->nullable();
            $table->foreignId('unit_id')->nullable()->constrained('units');
            $table->decimal('hourly_rate', 12, 3)->nullable();
            $table->decimal('daily_rate', 12, 3)->nullable();
            $table->decimal('monthly_rate', 12, 3)->nullable();
            $table->decimal('availability_percentage', 5, 2)->default(100);
            
            $table->timestamps();
        });
        }

        // تخصيص الموارد للأنشطة
        if (!Schema::hasTable('project_resource_assignments')) {
        Schema::create('project_resource_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wbs_id')->constrained('project_wbs')->cascadeOnDelete();
            $table->foreignId('resource_id')->constrained('project_resources')->cascadeOnDelete();
            
            $table->decimal('planned_quantity', 12, 4)->default(0);
            $table->decimal('actual_quantity', 12, 4)->default(0);
            $table->decimal('unit_rate', 12, 3)->default(0);
            $table->decimal('planned_cost', 18, 3)->default(0);
            $table->decimal('actual_cost', 18, 3)->default(0);
            
            $table->timestamps();
        });
        }

        // تحديثات التقدم
        if (!Schema::hasTable('project_progress_updates')) {
        Schema::create('project_progress_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wbs_id')->nullable()->constrained('project_wbs');
            
            $table->date('update_date');
            $table->date('data_date')->nullable();
            $table->decimal('planned_progress', 5, 2)->default(0);
            $table->decimal('actual_progress', 5, 2)->default(0);
            $table->integer('remaining_duration')->nullable();
            $table->text('notes')->nullable();
            
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });
        }

        // Baseline
        if (!Schema::hasTable('project_baselines')) {
        Schema::create('project_baselines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            
            $table->integer('baseline_number');
            $table->string('name');
            $table->date('baseline_date');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });
        }

        // بيانات Baseline للأنشطة
        if (!Schema::hasTable('project_baseline_details')) {
        Schema::create('project_baseline_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('baseline_id')->constrained('project_baselines')->cascadeOnDelete();
            $table->foreignId('wbs_id')->constrained('project_wbs')->cascadeOnDelete();
            
            $table->date('planned_start');
            $table->date('planned_finish');
            $table->integer('duration_days');
            $table->decimal('budget', 18, 3)->default(0);
            
            $table->timestamps();
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('project_baseline_details');
        Schema::dropIfExists('project_baselines');
        Schema::dropIfExists('project_progress_updates');
        Schema::dropIfExists('project_resource_assignments');
        Schema::dropIfExists('project_resources');
    }
};
