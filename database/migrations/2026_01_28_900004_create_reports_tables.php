<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // تعريفات التقارير
        Schema::create('report_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->enum('category', [
                'projects', 'contracts', 'finance', 'hr', 'warehouse', 
                'equipment', 'quality', 'hse', 'procurement', 'crm', 'executive'
            ]);
            $table->enum('type', ['operational', 'financial', 'management', 'compliance', 'analytical']);
            $table->text('description')->nullable();
            $table->string('model_class')->nullable(); // Laravel model to query
            $table->json('parameters')->nullable(); // Report parameters definition
            $table->json('columns')->nullable(); // Column definitions
            $table->json('filters')->nullable(); // Available filters
            $table->json('groupings')->nullable(); // Grouping options
            $table->json('charts')->nullable(); // Chart configurations
            $table->string('template')->nullable(); // Blade template name
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'quarterly', 'yearly', 'on_demand'])->default('on_demand');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->integer('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // سجل تشغيل التقارير
        Schema::create('report_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_definition_id')->constrained('report_definitions')->cascadeOnDelete();
            $table->json('parameters')->nullable();
            $table->json('filters_applied')->nullable();
            $table->dateTime('started_at');
            $table->dateTime('completed_at')->nullable();
            $table->integer('rows_count')->nullable();
            $table->enum('status', ['running', 'completed', 'failed', 'cancelled'])->default('running');
            $table->text('error_message')->nullable();
            $table->string('output_format')->default('html'); // html, pdf, excel, csv
            $table->string('file_path')->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->foreignId('executed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // تقارير مجدولة
        Schema::create('scheduled_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_definition_id')->constrained('report_definitions')->cascadeOnDelete();
            $table->string('name');
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'quarterly']);
            $table->string('cron_expression')->nullable();
            $table->time('run_time')->nullable();
            $table->integer('day_of_week')->nullable(); // 0-6
            $table->integer('day_of_month')->nullable(); // 1-31
            $table->json('parameters')->nullable();
            $table->json('recipients')->nullable(); // email addresses
            $table->string('output_format')->default('pdf');
            $table->boolean('is_active')->default(true);
            $table->dateTime('last_run')->nullable();
            $table->dateTime('next_run')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // لوحات المتابعة
        Schema::create('dashboards', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->text('description')->nullable();
            $table->enum('type', ['executive', 'project', 'financial', 'operational', 'custom'])->default('custom');
            $table->json('layout')->nullable(); // Grid layout configuration
            $table->boolean('is_default')->default(false);
            $table->boolean('is_public')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('refresh_interval')->default(300); // seconds
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // عناصر لوحة المتابعة (Widgets)
        Schema::create('dashboard_widgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dashboard_id')->constrained('dashboards')->cascadeOnDelete();
            $table->string('title');
            $table->enum('widget_type', [
                'kpi_card', 'chart', 'table', 'list', 'progress', 
                'gauge', 'map', 'calendar', 'timeline', 'custom'
            ]);
            $table->enum('chart_type', [
                'bar', 'line', 'pie', 'doughnut', 'area', 
                'scatter', 'radar', 'mixed', null
            ])->nullable();
            $table->string('data_source')->nullable(); // Model or API endpoint
            $table->json('query_config')->nullable(); // Query configuration
            $table->json('display_config')->nullable(); // Display settings
            $table->integer('width')->default(4); // Grid columns (1-12)
            $table->integer('height')->default(2); // Grid rows
            $table->integer('position_x')->default(0);
            $table->integer('position_y')->default(0);
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->integer('refresh_interval')->nullable();
            $table->timestamps();
        });

        // مؤشرات الأداء الرئيسية
        Schema::create('kpis', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->text('description')->nullable();
            $table->enum('category', [
                'financial', 'operational', 'quality', 'safety', 
                'hr', 'customer', 'project', 'procurement'
            ]);
            $table->string('unit')->nullable(); // %, JOD, days, etc.
            $table->string('data_source')->nullable();
            $table->text('calculation_formula')->nullable();
            $table->decimal('target_value', 18, 4)->nullable();
            $table->decimal('warning_threshold', 18, 4)->nullable();
            $table->decimal('critical_threshold', 18, 4)->nullable();
            $table->enum('comparison_type', ['higher_better', 'lower_better', 'target'])->default('higher_better');
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'])->default('monthly');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        // قيم مؤشرات الأداء
        Schema::create('kpi_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kpi_id')->constrained('kpis')->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->date('period_date');
            $table->integer('year');
            $table->integer('month')->nullable();
            $table->integer('week')->nullable();
            $table->decimal('value', 18, 4);
            $table->decimal('target_value', 18, 4)->nullable();
            $table->decimal('previous_value', 18, 4)->nullable();
            $table->enum('status', ['on_track', 'warning', 'critical'])->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->unique(['kpi_id', 'project_id', 'period_date']);
        });

        // تنبيهات المؤشرات
        Schema::create('kpi_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kpi_id')->constrained('kpis')->cascadeOnDelete();
            $table->foreignId('kpi_value_id')->nullable()->constrained('kpi_values')->nullOnDelete();
            $table->enum('alert_type', ['warning', 'critical']);
            $table->string('title');
            $table->text('message');
            $table->decimal('threshold_value', 18, 4)->nullable();
            $table->decimal('actual_value', 18, 4)->nullable();
            $table->boolean('is_read')->default(false);
            $table->boolean('is_acknowledged')->default(false);
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();
        });

        // التقارير المفضلة للمستخدم
        Schema::create('user_favorite_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('report_definition_id')->constrained('report_definitions')->cascadeOnDelete();
            $table->json('default_parameters')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'report_definition_id']);
        });

        // لوحات المتابعة المفضلة للمستخدم
        Schema::create('user_dashboards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('dashboard_id')->constrained('dashboards')->cascadeOnDelete();
            $table->boolean('is_default')->default(false);
            $table->json('custom_layout')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'dashboard_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_dashboards');
        Schema::dropIfExists('user_favorite_reports');
        Schema::dropIfExists('kpi_alerts');
        Schema::dropIfExists('kpi_values');
        Schema::dropIfExists('kpis');
        Schema::dropIfExists('dashboard_widgets');
        Schema::dropIfExists('dashboards');
        Schema::dropIfExists('scheduled_reports');
        Schema::dropIfExists('report_executions');
        Schema::dropIfExists('report_definitions');
    }
};
