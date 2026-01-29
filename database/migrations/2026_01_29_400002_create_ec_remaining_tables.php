<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ==========================================
        // 📋 PUNCH LISTS - قوائم التسليم
        // ==========================================

        // قوائم Punch
        Schema::create('punch_lists', function (Blueprint $table) {
            $table->id();
            $table->string('list_number', 50)->unique();
            $table->foreignId('project_id')->constrained('projects');
            $table->foreignId('contract_id')->nullable()->constrained('contracts');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('area')->nullable();
            $table->string('discipline')->nullable();
            $table->enum('list_type', ['pre_completion', 'substantial', 'final', 'warranty']);
            $table->date('walkthrough_date')->nullable();
            $table->date('due_date')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('responsible_contractor_id')->nullable()->constrained('suppliers');
            $table->integer('total_items')->default(0);
            $table->integer('completed_items')->default(0);
            $table->decimal('completion_percentage', 5, 2)->default(0);
            $table->enum('status', ['draft', 'issued', 'in_progress', 'pending_verification', 'closed'])->default('draft');
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // عناصر Punch
        Schema::create('punch_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('punch_list_id')->constrained('punch_lists')->cascadeOnDelete();
            $table->string('item_number', 20);
            $table->string('location');
            $table->string('discipline')->nullable();
            $table->text('description');
            $table->enum('priority', ['critical', 'major', 'minor', 'cosmetic'])->default('minor');
            $table->enum('category', ['construction', 'mechanical', 'electrical', 'plumbing', 'finishing', 'safety', 'other']);
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->foreignId('responsible_contractor_id')->nullable()->constrained('suppliers');
            $table->date('due_date')->nullable();
            $table->json('photos')->nullable();
            $table->text('contractor_response')->nullable();
            $table->date('completion_date')->nullable();
            $table->text('completion_notes')->nullable();
            $table->json('completion_photos')->nullable();
            $table->enum('status', ['open', 'in_progress', 'completed', 'verified', 'rejected', 'not_applicable'])->default('open');
            $table->foreignId('verified_by')->nullable()->constrained('users');
            $table->timestamp('verified_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->index(['punch_list_id', 'status']);
        });

        // ==========================================
        // 📈 EARNED VALUE MANAGEMENT - القيمة المكتسبة
        // ==========================================

        // لقطات EVM
        Schema::create('evm_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects');
            $table->date('snapshot_date');
            $table->date('data_date');
            $table->decimal('bac', 18, 2); // Budget at Completion
            $table->decimal('pv', 18, 2); // Planned Value (BCWS)
            $table->decimal('ev', 18, 2); // Earned Value (BCWP)
            $table->decimal('ac', 18, 2); // Actual Cost (ACWP)
            $table->decimal('sv', 18, 2); // Schedule Variance
            $table->decimal('cv', 18, 2); // Cost Variance
            $table->decimal('spi', 8, 4); // Schedule Performance Index
            $table->decimal('cpi', 8, 4); // Cost Performance Index
            $table->decimal('eac', 18, 2)->nullable(); // Estimate at Completion
            $table->decimal('etc', 18, 2)->nullable(); // Estimate to Complete
            $table->decimal('vac', 18, 2)->nullable(); // Variance at Completion
            $table->decimal('tcpi', 8, 4)->nullable(); // To Complete Performance Index
            $table->decimal('percent_complete', 5, 2)->default(0);
            $table->decimal('percent_spent', 5, 2)->default(0);
            $table->text('analysis_notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->unique(['project_id', 'snapshot_date']);
        });

        // مقاييس EVM بالتفصيل
        Schema::create('evm_wbs_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('snapshot_id')->constrained('evm_snapshots')->cascadeOnDelete();
            $table->foreignId('wbs_id')->constrained('project_wbs');
            $table->decimal('bac', 18, 2);
            $table->decimal('pv', 18, 2);
            $table->decimal('ev', 18, 2);
            $table->decimal('ac', 18, 2);
            $table->decimal('sv', 18, 2);
            $table->decimal('cv', 18, 2);
            $table->decimal('spi', 8, 4);
            $table->decimal('cpi', 8, 4);
            $table->decimal('percent_complete', 5, 2)->default(0);
            $table->timestamps();
        });

        // تقارير القيمة المكتسبة
        Schema::create('evm_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_number', 50)->unique();
            $table->foreignId('project_id')->constrained('projects');
            $table->foreignId('snapshot_id')->constrained('evm_snapshots');
            $table->string('title');
            $table->date('report_date');
            $table->date('period_from');
            $table->date('period_to');
            $table->text('executive_summary')->nullable();
            $table->text('schedule_analysis')->nullable();
            $table->text('cost_analysis')->nullable();
            $table->text('risks_issues')->nullable();
            $table->text('recommendations')->nullable();
            $table->enum('status', ['draft', 'submitted', 'approved'])->default('draft');
            $table->foreignId('prepared_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        // ==========================================
        // 🏗️ COMMISSIONING - التشغيل والتسليم
        // ==========================================

        // أنظمة التشغيل
        Schema::create('commissioning_systems', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects');
            $table->string('system_number', 50)->unique();
            $table->string('name_ar');
            $table->string('name_en');
            $table->text('description')->nullable();
            $table->enum('system_type', ['mechanical', 'electrical', 'plumbing', 'hvac', 'fire_protection', 'controls', 'structural', 'architectural', 'other']);
            $table->string('location')->nullable();
            $table->string('building')->nullable();
            $table->string('floor')->nullable();
            $table->foreignId('parent_system_id')->nullable()->constrained('commissioning_systems');
            $table->enum('priority', ['critical', 'high', 'medium', 'low'])->default('medium');
            $table->date('planned_start_date')->nullable();
            $table->date('planned_completion_date')->nullable();
            $table->date('actual_start_date')->nullable();
            $table->date('actual_completion_date')->nullable();
            $table->decimal('completion_percentage', 5, 2)->default(0);
            $table->enum('status', ['not_started', 'in_progress', 'ready_for_testing', 'testing', 'commissioned', 'on_hold'])->default('not_started');
            $table->foreignId('responsible_engineer_id')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        // قوائم فحص التشغيل
        Schema::create('commissioning_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('system_id')->constrained('commissioning_systems');
            $table->string('checklist_number', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('checklist_type', ['pre_functional', 'functional', 'performance', 'acceptance']);
            $table->enum('phase', ['level_1', 'level_2', 'level_3', 'level_4', 'level_5']);
            $table->integer('total_items')->default(0);
            $table->integer('passed_items')->default(0);
            $table->integer('failed_items')->default(0);
            $table->integer('na_items')->default(0);
            $table->date('scheduled_date')->nullable();
            $table->date('executed_date')->nullable();
            $table->foreignId('executed_by')->nullable()->constrained('users');
            $table->foreignId('witnessed_by')->nullable()->constrained('users');
            $table->enum('status', ['not_started', 'in_progress', 'completed', 'failed', 'requires_retest'])->default('not_started');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // عناصر فحص التشغيل
        Schema::create('commissioning_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_id')->constrained('commissioning_checklists')->cascadeOnDelete();
            $table->integer('item_number');
            $table->text('test_description');
            $table->string('acceptance_criteria')->nullable();
            $table->string('expected_value')->nullable();
            $table->string('actual_value')->nullable();
            $table->enum('result', ['pass', 'fail', 'na', 'pending'])->default('pending');
            $table->text('remarks')->nullable();
            $table->foreignId('punch_item_id')->nullable()->constrained('punch_items');
            $table->timestamps();
        });

        // إجراءات التشغيل
        Schema::create('startup_procedures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('system_id')->constrained('commissioning_systems');
            $table->string('procedure_number', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('procedure_type', ['pre_startup', 'startup', 'shutdown', 'emergency_shutdown']);
            $table->json('steps');
            $table->json('required_resources')->nullable();
            $table->json('safety_precautions')->nullable();
            $table->enum('status', ['draft', 'approved', 'executed'])->default('draft');
            $table->foreignId('prepared_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->foreignId('executed_by')->nullable()->constrained('users');
            $table->text('execution_notes')->nullable();
            $table->timestamps();
        });

        // ==========================================
        // 📄 AS-BUILT DOCUMENTATION
        // ==========================================

        // سجلات As-Built
        Schema::create('asbuilt_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects');
            $table->string('record_number', 50)->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('document_type', ['drawing', 'specification', 'manual', 'certificate', 'test_report', 'warranty', 'other']);
            $table->string('discipline')->nullable();
            $table->string('area')->nullable();
            $table->string('system')->nullable();
            $table->string('original_document_number')->nullable();
            $table->string('revision')->nullable();
            $table->date('document_date')->nullable();
            $table->string('file_path')->nullable();
            $table->enum('status', ['pending', 'submitted', 'reviewed', 'approved', 'rejected'])->default('pending');
            $table->foreignId('submitted_by')->nullable()->constrained('users');
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_comments')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // حزم تسليم As-Built
        Schema::create('asbuilt_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects');
            $table->string('package_number', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('discipline')->nullable();
            $table->date('due_date')->nullable();
            $table->date('submission_date')->nullable();
            $table->integer('total_documents')->default(0);
            $table->integer('approved_documents')->default(0);
            $table->decimal('completion_percentage', 5, 2)->default(0);
            $table->enum('status', ['not_started', 'in_progress', 'submitted', 'under_review', 'approved', 'rejected'])->default('not_started');
            $table->foreignId('responsible_id')->nullable()->constrained('users');
            $table->timestamps();
        });

        // ربط السجلات بالحزم
        Schema::create('asbuilt_package_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained('asbuilt_packages')->cascadeOnDelete();
            $table->foreignId('record_id')->constrained('asbuilt_records')->cascadeOnDelete();
            $table->integer('sequence')->default(0);
            $table->timestamps();
        });

        // ==========================================
        // 📦 PROJECT PROCUREMENT - مشتريات المشاريع
        // ==========================================

        // طلبات شراء المشاريع
        Schema::create('project_purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number', 50)->unique();
            $table->foreignId('project_id')->constrained('projects');
            $table->foreignId('wbs_id')->nullable()->constrained('project_wbs');
            $table->foreignId('cost_code_id')->nullable()->constrained('project_costs');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('request_type', ['material', 'equipment', 'service', 'subcontract']);
            $table->enum('urgency', ['routine', 'urgent', 'critical'])->default('routine');
            $table->date('required_date');
            $table->string('delivery_location')->nullable();
            $table->decimal('estimated_value', 18, 2)->nullable();
            $table->string('currency', 3)->default('JOD');
            $table->enum('status', ['draft', 'submitted', 'approved', 'in_procurement', 'ordered', 'delivered', 'cancelled'])->default('draft');
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // عناصر طلب الشراء
        Schema::create('project_purchase_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('project_purchase_requests')->cascadeOnDelete();
            $table->integer('line_number');
            $table->string('item_code')->nullable();
            $table->text('description');
            $table->string('specification')->nullable();
            $table->decimal('quantity', 18, 4);
            $table->string('unit');
            $table->decimal('unit_price', 18, 4)->nullable();
            $table->decimal('total_price', 18, 2)->nullable();
            $table->date('required_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // تتبع توريدات المشاريع
        Schema::create('project_material_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects');
            $table->foreignId('purchase_order_id')->nullable();
            $table->string('item_code')->nullable();
            $table->text('description');
            $table->decimal('ordered_quantity', 18, 4);
            $table->decimal('received_quantity', 18, 4)->default(0);
            $table->decimal('installed_quantity', 18, 4)->default(0);
            $table->string('unit');
            $table->date('required_date')->nullable();
            $table->date('promised_date')->nullable();
            $table->date('actual_delivery_date')->nullable();
            $table->string('delivery_location')->nullable();
            $table->enum('status', ['ordered', 'in_transit', 'partially_received', 'received', 'in_storage', 'installed'])->default('ordered');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // ==========================================
        // 🔍 QUALITY MANAGEMENT
        // ==========================================

        // خطط الجودة
        Schema::create('quality_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects');
            $table->string('plan_number', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('discipline')->nullable();
            $table->date('effective_date');
            $table->string('revision', 10)->default('0');
            $table->json('applicable_specifications')->nullable();
            $table->json('inspection_points')->nullable();
            $table->json('hold_points')->nullable();
            $table->json('witness_points')->nullable();
            $table->enum('status', ['draft', 'submitted', 'approved', 'superseded'])->default('draft');
            $table->foreignId('prepared_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        // طلبات التفتيش
        Schema::create('inspection_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number', 50)->unique();
            $table->foreignId('project_id')->constrained('projects');
            $table->foreignId('quality_plan_id')->nullable()->constrained('quality_plans');
            $table->string('work_activity');
            $table->string('location');
            $table->string('discipline')->nullable();
            $table->text('description');
            $table->enum('inspection_type', ['hold_point', 'witness_point', 'surveillance', 'final']);
            $table->date('requested_date');
            $table->time('requested_time')->nullable();
            $table->foreignId('requested_by')->constrained('users');
            $table->enum('status', ['pending', 'scheduled', 'completed', 'cancelled'])->default('pending');
            $table->date('inspection_date')->nullable();
            $table->foreignId('inspector_id')->nullable()->constrained('users');
            $table->enum('result', ['approved', 'approved_with_comments', 'rejected', 'not_ready'])->nullable();
            $table->text('comments')->nullable();
            $table->json('attachments')->nullable();
            $table->timestamps();
        });

        // ==========================================
        // 📊 PRODUCTIVITY & PERFORMANCE
        // ==========================================

        // قياس الإنتاجية
        Schema::create('productivity_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects');
            $table->foreignId('wbs_id')->nullable()->constrained('project_wbs');
            $table->date('record_date');
            $table->string('activity_code')->nullable();
            $table->string('activity_description');
            $table->string('unit');
            $table->decimal('planned_quantity', 18, 4);
            $table->decimal('actual_quantity', 18, 4);
            $table->decimal('planned_manhours', 10, 2);
            $table->decimal('actual_manhours', 10, 2);
            $table->decimal('planned_productivity', 10, 4);
            $table->decimal('actual_productivity', 10, 4);
            $table->decimal('productivity_factor', 8, 4);
            $table->integer('crew_size')->nullable();
            $table->text('conditions')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->constrained('users');
            $table->timestamps();
            $table->index(['project_id', 'record_date']);
        });

        // سجلات الطقس
        Schema::create('weather_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects');
            $table->date('log_date');
            $table->time('log_time')->nullable();
            $table->decimal('temperature_high', 5, 2)->nullable();
            $table->decimal('temperature_low', 5, 2)->nullable();
            $table->string('conditions')->nullable();
            $table->decimal('precipitation_mm', 8, 2)->default(0);
            $table->decimal('wind_speed_kmh', 8, 2)->nullable();
            $table->string('wind_direction')->nullable();
            $table->decimal('humidity_percent', 5, 2)->nullable();
            $table->boolean('work_impacted')->default(false);
            $table->decimal('lost_hours', 8, 2)->default(0);
            $table->text('impact_description')->nullable();
            $table->foreignId('recorded_by')->constrained('users');
            $table->timestamps();
            $table->unique(['project_id', 'log_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weather_logs');
        Schema::dropIfExists('productivity_records');
        Schema::dropIfExists('inspection_requests');
        Schema::dropIfExists('quality_plans');
        Schema::dropIfExists('project_material_tracking');
        Schema::dropIfExists('project_purchase_request_items');
        Schema::dropIfExists('project_purchase_requests');
        Schema::dropIfExists('asbuilt_package_documents');
        Schema::dropIfExists('asbuilt_packages');
        Schema::dropIfExists('asbuilt_records');
        Schema::dropIfExists('startup_procedures');
        Schema::dropIfExists('commissioning_checklist_items');
        Schema::dropIfExists('commissioning_checklists');
        Schema::dropIfExists('commissioning_systems');
        Schema::dropIfExists('evm_reports');
        Schema::dropIfExists('evm_wbs_metrics');
        Schema::dropIfExists('evm_snapshots');
        Schema::dropIfExists('punch_items');
        Schema::dropIfExists('punch_lists');
    }
};
