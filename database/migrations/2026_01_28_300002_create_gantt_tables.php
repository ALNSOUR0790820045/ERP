<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * جداول Gantt Chart
     */
    public function up(): void
    {
        // جدول مهام Gantt
        if (!Schema::hasTable('gantt_tasks')) {
            Schema::create('gantt_tasks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->foreignId('parent_id')->nullable()->constrained('gantt_tasks')->nullOnDelete();
                
                $table->string('task_code')->nullable();
                $table->string('name');
                $table->text('description')->nullable();
                
                // التواريخ
                $table->date('planned_start');
                $table->date('planned_end');
                $table->date('actual_start')->nullable();
                $table->date('actual_end')->nullable();
                $table->integer('duration_days')->default(1);
                
                // التقدم
                $table->decimal('progress', 5, 2)->default(0);
                $table->decimal('weight', 5, 2)->default(0);
                
                // النوع
                $table->enum('task_type', [
                    'task', // مهمة عادية
                    'milestone', // معلم
                    'summary', // ملخص
                    'project', // مشروع فرعي
                ]);
                
                // الحالة
                $table->enum('status', [
                    'not_started',
                    'in_progress',
                    'completed',
                    'on_hold',
                    'cancelled',
                ])->default('not_started');
                
                // الأولوية
                $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
                
                // المسار الحرج
                $table->boolean('is_critical')->default(false);
                
                // الترتيب
                $table->integer('sort_order')->default(0);
                $table->integer('level')->default(0);
                $table->string('wbs_code')->nullable(); // Work Breakdown Structure
                
                // الموارد
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->decimal('estimated_hours', 10, 2)->nullable();
                $table->decimal('actual_hours', 10, 2)->nullable();
                $table->decimal('estimated_cost', 15, 3)->nullable();
                $table->decimal('actual_cost', 15, 3)->nullable();
                
                // الألوان
                $table->string('color')->nullable();
                
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();
                
                $table->index(['project_id', 'parent_id']);
                $table->index(['planned_start', 'planned_end']);
            });
        }

        // جدول العلاقات بين المهام (Dependencies)
        if (!Schema::hasTable('gantt_dependencies')) {
            Schema::create('gantt_dependencies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('predecessor_id')->constrained('gantt_tasks')->cascadeOnDelete();
                $table->foreignId('successor_id')->constrained('gantt_tasks')->cascadeOnDelete();
                
                // نوع العلاقة
                $table->enum('dependency_type', [
                    'FS', // Finish-to-Start (الأكثر شيوعاً)
                    'FF', // Finish-to-Finish
                    'SS', // Start-to-Start
                    'SF', // Start-to-Finish
                ])->default('FS');
                
                $table->integer('lag_days')->default(0); // التأخير بالأيام
                
                $table->timestamps();
                
                $table->unique(['predecessor_id', 'successor_id']);
            });
        }

        // جدول الموارد
        if (!Schema::hasTable('gantt_resources')) {
            Schema::create('gantt_resources', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->enum('type', ['human', 'equipment', 'material'])->default('human');
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->decimal('cost_per_hour', 15, 3)->nullable();
                $table->decimal('cost_per_unit', 15, 3)->nullable();
                $table->integer('available_hours_per_day')->default(8);
                $table->string('calendar')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // جدول تخصيص الموارد للمهام
        if (!Schema::hasTable('gantt_task_resources')) {
            Schema::create('gantt_task_resources', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->constrained('gantt_tasks')->cascadeOnDelete();
                $table->foreignId('resource_id')->constrained('gantt_resources')->cascadeOnDelete();
                $table->decimal('units', 5, 2)->default(100); // نسبة التخصيص
                $table->decimal('planned_hours', 10, 2)->nullable();
                $table->decimal('actual_hours', 10, 2)->nullable();
                $table->decimal('cost', 15, 3)->nullable();
                $table->timestamps();
                
                $table->unique(['task_id', 'resource_id']);
            });
        }

        // جدول خطوط الأساس للـ Gantt
        if (!Schema::hasTable('gantt_baselines')) {
            Schema::create('gantt_baselines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->text('description')->nullable();
                $table->date('baseline_date');
                $table->boolean('is_current')->default(false);
                $table->json('tasks_snapshot')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('gantt_baselines');
        Schema::dropIfExists('gantt_task_resources');
        Schema::dropIfExists('gantt_resources');
        Schema::dropIfExists('gantt_dependencies');
        Schema::dropIfExists('gantt_tasks');
    }
};
