<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. P6 Integration - Import/Export Logs
        Schema::create('p6_import_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['import', 'export']);
            $table->enum('format', ['xer', 'xml', 'xls']);
            $table->string('file_name');
            $table->string('file_path')->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->integer('total_activities')->default(0);
            $table->integer('processed_activities')->default(0);
            $table->integer('total_resources')->default(0);
            $table->integer('processed_resources')->default(0);
            $table->integer('errors_count')->default(0);
            $table->json('error_log')->nullable();
            $table->json('mapping_config')->nullable();
            $table->json('options')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        // 2. P6 Activity Mapping
        Schema::create('p6_activity_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('p6_import_export_id')->constrained()->cascadeOnDelete();
            $table->string('p6_activity_id');
            $table->string('p6_activity_name');
            $table->foreignId('gantt_task_id')->nullable()->constrained('gantt_tasks')->nullOnDelete();
            $table->foreignId('project_wbs_id')->nullable()->constrained('project_wbs')->nullOnDelete();
            $table->enum('mapping_status', ['mapped', 'created', 'skipped', 'error'])->default('mapped');
            $table->json('p6_data')->nullable();
            $table->json('mapping_notes')->nullable();
            $table->timestamps();
        });

        // 3. P6 Resource Mapping
        Schema::create('p6_resource_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('p6_import_export_id')->constrained()->cascadeOnDelete();
            $table->string('p6_resource_id');
            $table->string('p6_resource_name');
            $table->foreignId('project_resource_id')->nullable()->constrained('project_resources')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('equipment_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('resource_type', ['labor', 'equipment', 'material', 'expense']);
            $table->enum('mapping_status', ['mapped', 'created', 'skipped', 'error'])->default('mapped');
            $table->json('p6_data')->nullable();
            $table->timestamps();
        });

        // 4. MS Project Integration
        Schema::create('msp_import_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['import', 'export']);
            $table->enum('format', ['mpp', 'mspdi', 'xml', 'csv']);
            $table->string('file_name');
            $table->string('file_path')->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->string('msp_version')->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->integer('total_tasks')->default(0);
            $table->integer('processed_tasks')->default(0);
            $table->integer('total_resources')->default(0);
            $table->integer('processed_resources')->default(0);
            $table->integer('errors_count')->default(0);
            $table->json('error_log')->nullable();
            $table->json('mapping_config')->nullable();
            $table->json('options')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        // 5. MSP Task Mapping
        Schema::create('msp_task_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('msp_import_export_id')->constrained()->cascadeOnDelete();
            $table->integer('msp_task_uid');
            $table->string('msp_task_name');
            $table->integer('msp_outline_level')->default(0);
            $table->string('msp_wbs_code')->nullable();
            $table->foreignId('gantt_task_id')->nullable()->constrained('gantt_tasks')->nullOnDelete();
            $table->enum('mapping_status', ['mapped', 'created', 'skipped', 'error'])->default('mapped');
            $table->json('msp_data')->nullable();
            $table->timestamps();
        });

        // 6. Monte Carlo Simulation
        Schema::create('monte_carlo_simulations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('iterations')->default(1000);
            $table->enum('distribution_type', ['triangular', 'beta', 'normal', 'uniform', 'pert'])->default('triangular');
            $table->decimal('confidence_level', 5, 2)->default(80.00);
            $table->date('baseline_finish_date')->nullable();
            $table->decimal('baseline_cost', 20, 2)->nullable();
            $table->enum('status', ['draft', 'running', 'completed', 'failed'])->default('draft');
            $table->json('input_parameters')->nullable();
            $table->json('results')->nullable();
            $table->date('p50_finish_date')->nullable();
            $table->date('p80_finish_date')->nullable();
            $table->date('p90_finish_date')->nullable();
            $table->decimal('p50_cost', 20, 2)->nullable();
            $table->decimal('p80_cost', 20, 2)->nullable();
            $table->decimal('p90_cost', 20, 2)->nullable();
            $table->integer('schedule_risk_days')->nullable();
            $table->decimal('cost_risk_amount', 20, 2)->nullable();
            $table->decimal('criticality_index', 5, 2)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        // 7. Monte Carlo Activity Inputs
        Schema::create('monte_carlo_activity_inputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monte_carlo_simulation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gantt_task_id')->constrained('gantt_tasks')->cascadeOnDelete();
            $table->integer('optimistic_duration');
            $table->integer('most_likely_duration');
            $table->integer('pessimistic_duration');
            $table->decimal('optimistic_cost', 15, 2)->nullable();
            $table->decimal('most_likely_cost', 15, 2)->nullable();
            $table->decimal('pessimistic_cost', 15, 2)->nullable();
            $table->decimal('correlation_coefficient', 5, 4)->nullable();
            $table->boolean('is_critical_driver')->default(false);
            $table->timestamps();
        });

        // 8. Monte Carlo Results
        Schema::create('monte_carlo_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monte_carlo_simulation_id')->constrained()->cascadeOnDelete();
            $table->integer('iteration_number');
            $table->date('simulated_finish_date');
            $table->decimal('simulated_cost', 20, 2)->nullable();
            $table->integer('simulated_duration_days');
            $table->json('critical_path_activities')->nullable();
            $table->json('activity_durations')->nullable();
            $table->timestamps();

            $table->index(['monte_carlo_simulation_id', 'iteration_number']);
        });

        // 9. What-If Scenarios
        Schema::create('what_if_scenarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('scenario_type', ['schedule', 'cost', 'resource', 'combined'])->default('combined');
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft');
            $table->boolean('is_baseline')->default(false);
            $table->date('baseline_start_date')->nullable();
            $table->date('baseline_end_date')->nullable();
            $table->decimal('baseline_cost', 20, 2)->nullable();
            $table->date('scenario_start_date')->nullable();
            $table->date('scenario_end_date')->nullable();
            $table->decimal('scenario_cost', 20, 2)->nullable();
            $table->integer('schedule_variance_days')->nullable();
            $table->decimal('cost_variance', 20, 2)->nullable();
            $table->decimal('schedule_variance_percent', 8, 2)->nullable();
            $table->decimal('cost_variance_percent', 8, 2)->nullable();
            $table->json('assumptions')->nullable();
            $table->json('impact_summary')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        // 10. What-If Scenario Changes
        Schema::create('what_if_scenario_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('what_if_scenario_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gantt_task_id')->nullable()->constrained('gantt_tasks')->nullOnDelete();
            $table->foreignId('project_resource_id')->nullable()->constrained('project_resources')->nullOnDelete();
            $table->enum('change_type', ['duration', 'start_date', 'end_date', 'cost', 'resource', 'dependency', 'add_task', 'remove_task']);
            $table->string('field_name');
            $table->text('original_value')->nullable();
            $table->text('new_value')->nullable();
            $table->text('reason')->nullable();
            $table->decimal('impact_days', 10, 2)->nullable();
            $table->decimal('impact_cost', 15, 2)->nullable();
            $table->timestamps();
        });

        // 11. Resource Optimization
        Schema::create('resource_optimizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('optimization_type', ['leveling', 'smoothing', 'allocation', 'cost_optimization']);
            $table->enum('priority', ['time', 'cost', 'resource', 'balanced'])->default('balanced');
            $table->date('optimization_start_date');
            $table->date('optimization_end_date');
            $table->boolean('respect_dependencies')->default(true);
            $table->boolean('level_within_slack')->default(true);
            $table->integer('max_delay_days')->nullable();
            $table->enum('status', ['draft', 'running', 'completed', 'applied', 'rejected'])->default('draft');
            $table->date('original_finish_date')->nullable();
            $table->date('optimized_finish_date')->nullable();
            $table->decimal('original_cost', 20, 2)->nullable();
            $table->decimal('optimized_cost', 20, 2)->nullable();
            $table->decimal('resource_utilization_before', 5, 2)->nullable();
            $table->decimal('resource_utilization_after', 5, 2)->nullable();
            $table->integer('overallocations_before')->default(0);
            $table->integer('overallocations_after')->default(0);
            $table->json('optimization_results')->nullable();
            $table->json('conflicts_resolved')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();
        });

        // 12. Resource Optimization Details
        Schema::create('resource_optimization_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resource_optimization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gantt_task_id')->constrained('gantt_tasks')->cascadeOnDelete();
            $table->foreignId('project_resource_id')->nullable()->constrained('project_resources')->nullOnDelete();
            $table->date('original_start_date');
            $table->date('original_end_date');
            $table->date('optimized_start_date');
            $table->date('optimized_end_date');
            $table->integer('delay_days')->default(0);
            $table->decimal('original_units', 10, 2)->nullable();
            $table->decimal('optimized_units', 10, 2)->nullable();
            $table->text('change_reason')->nullable();
            $table->boolean('is_applied')->default(false);
            $table->timestamps();
        });

        // 13. Time Impact Analysis
        Schema::create('time_impact_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('extension_of_time_id')->nullable()->constrained('extensions_of_time')->nullOnDelete();
            $table->string('analysis_number')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('delay_type', ['excusable_compensable', 'excusable_non_compensable', 'non_excusable', 'concurrent']);
            $table->enum('analysis_method', ['as_planned', 'as_built', 'impacted_as_planned', 'collapsed_as_built', 'time_impact', 'windows']);
            $table->date('event_start_date');
            $table->date('event_end_date')->nullable();
            $table->date('data_date');
            $table->date('baseline_completion_date');
            $table->date('impacted_completion_date')->nullable();
            $table->integer('delay_days')->nullable();
            $table->integer('concurrent_delay_days')->default(0);
            $table->integer('pacing_delay_days')->default(0);
            $table->integer('net_delay_days')->nullable();
            $table->json('impacted_activities')->nullable();
            $table->json('critical_path_before')->nullable();
            $table->json('critical_path_after')->nullable();
            $table->text('analysis_narrative')->nullable();
            $table->text('conclusion')->nullable();
            $table->enum('status', ['draft', 'in_review', 'approved', 'rejected', 'submitted'])->default('draft');
            $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });

        // 14. Time Impact Fragments
        Schema::create('time_impact_fragments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('time_impact_analysis_id')->constrained()->cascadeOnDelete();
            $table->string('fragment_id');
            $table->string('fragment_name');
            $table->foreignId('predecessor_task_id')->nullable()->constrained('gantt_tasks')->nullOnDelete();
            $table->foreignId('successor_task_id')->nullable()->constrained('gantt_tasks')->nullOnDelete();
            $table->date('fragment_start_date');
            $table->date('fragment_end_date');
            $table->integer('fragment_duration');
            $table->enum('dependency_type', ['FS', 'FF', 'SS', 'SF'])->default('FS');
            $table->integer('lag_days')->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 15. Forensic Schedule Analysis
        Schema::create('forensic_schedule_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('analysis_number')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('analysis_type', ['delay', 'disruption', 'acceleration', 'loss_of_productivity']);
            $table->enum('methodology', ['as_planned_vs_as_built', 'impacted_as_planned', 'collapsed_as_built', 'time_impact', 'windows', 'daily_delay']);
            $table->date('analysis_period_start');
            $table->date('analysis_period_end');
            $table->date('contract_completion_date');
            $table->date('actual_completion_date')->nullable();
            $table->date('extended_completion_date')->nullable();
            $table->integer('total_delay_days')->nullable();
            $table->integer('contractor_delay_days')->default(0);
            $table->integer('owner_delay_days')->default(0);
            $table->integer('concurrent_delay_days')->default(0);
            $table->integer('excusable_delay_days')->default(0);
            $table->integer('compensable_delay_days')->default(0);
            $table->json('delay_events')->nullable();
            $table->json('critical_path_changes')->nullable();
            $table->json('float_consumption')->nullable();
            $table->text('findings')->nullable();
            $table->text('recommendations')->nullable();
            $table->enum('status', ['draft', 'in_progress', 'completed', 'peer_reviewed', 'final'])->default('draft');
            $table->foreignId('analyst_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 16. Forensic Delay Events
        Schema::create('forensic_delay_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('forensic_schedule_analysis_id')->constrained()->cascadeOnDelete();
            $table->string('event_id');
            $table->string('event_name');
            $table->text('event_description')->nullable();
            $table->enum('responsible_party', ['contractor', 'owner', 'third_party', 'force_majeure', 'concurrent']);
            $table->enum('delay_category', ['excusable_compensable', 'excusable_non_compensable', 'non_excusable', 'concurrent']);
            $table->date('event_start_date');
            $table->date('event_end_date');
            $table->integer('gross_delay_days');
            $table->integer('concurrent_delay_days')->default(0);
            $table->integer('net_delay_days');
            $table->json('affected_activities')->nullable();
            $table->boolean('critical_path_impact')->default(false);
            $table->decimal('cost_impact', 15, 2)->nullable();
            $table->text('supporting_documents')->nullable();
            $table->text('mitigation_efforts')->nullable();
            $table->timestamps();
        });

        // 17. BIM Integration
        Schema::create('bim_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('model_type', ['architectural', 'structural', 'mep', 'civil', 'combined', 'coordination']);
            $table->string('file_name');
            $table->string('file_path');
            $table->bigInteger('file_size')->nullable();
            $table->enum('file_format', ['ifc', 'rvt', 'nwd', 'nwc', 'dwg', 'dgn', 'skp', 'fbx']);
            $table->string('software_name')->nullable();
            $table->string('software_version')->nullable();
            $table->string('ifc_schema_version')->nullable();
            $table->string('model_version')->nullable();
            $table->enum('lod', ['100', '200', '300', '350', '400', '500'])->nullable();
            $table->json('georeferencing')->nullable();
            $table->json('model_units')->nullable();
            $table->integer('elements_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 18. BIM Element Links (4D/5D)
        Schema::create('bim_element_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bim_model_id')->constrained()->cascadeOnDelete();
            $table->string('element_guid');
            $table->string('element_name')->nullable();
            $table->string('element_type')->nullable();
            $table->string('ifc_class')->nullable();
            $table->foreignId('gantt_task_id')->nullable()->constrained('gantt_tasks')->nullOnDelete();
            $table->foreignId('boq_item_id')->nullable()->constrained('boq_items')->nullOnDelete();
            $table->foreignId('project_wbs_id')->nullable()->constrained('project_wbs')->nullOnDelete();
            $table->decimal('quantity', 15, 4)->nullable();
            $table->string('quantity_unit')->nullable();
            $table->decimal('unit_cost', 15, 2)->nullable();
            $table->decimal('total_cost', 15, 2)->nullable();
            $table->enum('status', ['not_started', 'in_progress', 'completed'])->default('not_started');
            $table->decimal('progress_percent', 5, 2)->default(0);
            $table->json('properties')->nullable();
            $table->json('materials')->nullable();
            $table->timestamps();

            $table->index(['bim_model_id', 'element_guid']);
        });

        // 19. Multi-Currency Project Costs
        Schema::create('multi_currency_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gantt_task_id')->nullable()->constrained('gantt_tasks')->nullOnDelete();
            $table->foreignId('project_cost_id')->nullable()->constrained('project_costs')->nullOnDelete();
            $table->enum('cost_type', ['labor', 'material', 'equipment', 'subcontract', 'overhead', 'contingency', 'other']);
            $table->string('description');
            $table->string('original_currency', 3);
            $table->decimal('original_amount', 20, 2);
            $table->decimal('exchange_rate', 15, 6);
            $table->date('exchange_rate_date');
            $table->string('base_currency', 3)->default('JOD');
            $table->decimal('base_amount', 20, 2);
            $table->enum('exchange_rate_type', ['spot', 'forward', 'budget', 'actual'])->default('spot');
            $table->decimal('hedge_rate', 15, 6)->nullable();
            $table->decimal('variance_amount', 20, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'original_currency']);
        });

        // 20. Currency Exchange Rate History
        Schema::create('project_exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('from_currency', 3);
            $table->string('to_currency', 3);
            $table->decimal('exchange_rate', 15, 6);
            $table->date('effective_date');
            $table->date('expiry_date')->nullable();
            $table->enum('rate_type', ['budget', 'forecast', 'actual', 'hedged']);
            $table->string('source')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['project_id', 'from_currency', 'to_currency', 'effective_date', 'rate_type'], 'project_exchange_rates_unique');
        });

        // 21. Resource Calendars
        Schema::create('resource_calendars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('calendar_type', ['standard', 'shift', 'custom', 'holiday']);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_global')->default(false);
            $table->time('default_start_time')->default('08:00:00');
            $table->time('default_end_time')->default('17:00:00');
            $table->decimal('hours_per_day', 4, 2)->default(8.00);
            $table->decimal('hours_per_week', 5, 2)->default(40.00);
            $table->decimal('days_per_month', 4, 2)->default(22.00);
            $table->json('working_days')->nullable();
            $table->foreignId('parent_calendar_id')->nullable()->constrained('resource_calendars')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 22. Resource Calendar Exceptions
        Schema::create('resource_calendar_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resource_calendar_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('exception_type', ['holiday', 'working', 'non_working', 'modified_hours']);
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_recurring')->default(false);
            $table->enum('recurrence_pattern', ['daily', 'weekly', 'monthly', 'yearly'])->nullable();
            $table->integer('recurrence_interval')->nullable();
            $table->date('recurrence_end_date')->nullable();
            $table->time('working_start_time')->nullable();
            $table->time('working_end_time')->nullable();
            $table->decimal('working_hours', 4, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 23. Resource Calendar Assignments
        Schema::create('resource_calendar_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resource_calendar_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_resource_id')->nullable()->constrained('project_resources')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('equipment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('gantt_task_id')->nullable()->constrained('gantt_tasks')->nullOnDelete();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_calendar_assignments');
        Schema::dropIfExists('resource_calendar_exceptions');
        Schema::dropIfExists('resource_calendars');
        Schema::dropIfExists('project_exchange_rates');
        Schema::dropIfExists('multi_currency_costs');
        Schema::dropIfExists('bim_element_links');
        Schema::dropIfExists('bim_models');
        Schema::dropIfExists('forensic_delay_events');
        Schema::dropIfExists('forensic_schedule_analyses');
        Schema::dropIfExists('time_impact_fragments');
        Schema::dropIfExists('time_impact_analyses');
        Schema::dropIfExists('resource_optimization_details');
        Schema::dropIfExists('resource_optimizations');
        Schema::dropIfExists('what_if_scenario_changes');
        Schema::dropIfExists('what_if_scenarios');
        Schema::dropIfExists('monte_carlo_results');
        Schema::dropIfExists('monte_carlo_activity_inputs');
        Schema::dropIfExists('monte_carlo_simulations');
        Schema::dropIfExists('msp_task_mappings');
        Schema::dropIfExists('msp_import_exports');
        Schema::dropIfExists('p6_resource_mappings');
        Schema::dropIfExists('p6_activity_mappings');
        Schema::dropIfExists('p6_import_exports');
    }
};
