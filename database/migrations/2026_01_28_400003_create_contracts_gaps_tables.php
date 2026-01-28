<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // معالم العقد
        if (!Schema::hasTable('contract_milestones')) {
            Schema::create('contract_milestones', function (Blueprint $table) {
                $table->id();
                $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->text('description')->nullable();
                $table->date('planned_date');
                $table->date('actual_date')->nullable();
                $table->decimal('payment_percent', 5, 2)->nullable();
                $table->decimal('payment_amount', 15, 3)->nullable();
                $table->integer('sequence')->default(0);
                $table->enum('status', ['pending', 'in_progress', 'completed', 'delayed'])->default('pending');
                $table->timestamps();
            });
        }

        // جدول الدفعات
        if (!Schema::hasTable('contract_payment_schedules')) {
            Schema::create('contract_payment_schedules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
                $table->integer('payment_number');
                $table->string('description')->nullable();
                $table->date('due_date');
                $table->decimal('amount', 15, 3);
                $table->decimal('percentage', 5, 2)->nullable();
                $table->enum('payment_type', ['advance', 'milestone', 'interim', 'final', 'retention'])->default('interim');
                $table->enum('status', ['scheduled', 'invoiced', 'paid', 'overdue'])->default('scheduled');
                $table->date('actual_payment_date')->nullable();
                $table->decimal('actual_amount', 15, 3)->nullable();
                $table->timestamps();
            });
        }

        // الغرامات
        if (!Schema::hasTable('contract_penalties')) {
            Schema::create('contract_penalties', function (Blueprint $table) {
                $table->id();
                $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
                $table->enum('penalty_type', ['delay', 'quality', 'safety', 'other'])->default('delay');
                $table->string('description');
                $table->date('penalty_date');
                $table->decimal('amount', 15, 3);
                $table->decimal('percentage', 5, 2)->nullable();
                $table->integer('delay_days')->nullable();
                $table->enum('status', ['pending', 'applied', 'waived', 'disputed'])->default('pending');
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // المكافآت
        if (!Schema::hasTable('contract_bonuses')) {
            Schema::create('contract_bonuses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
                $table->enum('bonus_type', ['early_completion', 'quality', 'performance', 'other'])->default('performance');
                $table->string('description');
                $table->date('bonus_date');
                $table->decimal('amount', 15, 3);
                $table->integer('days_early')->nullable();
                $table->enum('status', ['pending', 'approved', 'paid'])->default('pending');
                $table->text('notes')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // ملاحق العقد
        if (!Schema::hasTable('contract_amendments')) {
            Schema::create('contract_amendments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
                $table->string('amendment_number');
                $table->date('amendment_date');
                $table->enum('amendment_type', ['scope', 'price', 'time', 'terms', 'combination'])->default('combination');
                $table->text('description');
                $table->decimal('original_value', 15, 3)->nullable();
                $table->decimal('change_value', 15, 3)->nullable();
                $table->decimal('new_value', 15, 3)->nullable();
                $table->date('original_end_date')->nullable();
                $table->date('new_end_date')->nullable();
                $table->integer('time_extension_days')->nullable();
                $table->enum('status', ['draft', 'pending', 'approved', 'rejected'])->default('draft');
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
            });
        }

        // إيقاف العقد
        if (!Schema::hasTable('contract_suspensions')) {
            Schema::create('contract_suspensions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
                $table->date('suspension_date');
                $table->date('resumption_date')->nullable();
                $table->integer('suspension_days')->nullable();
                $table->text('reason');
                $table->enum('initiated_by', ['employer', 'contractor', 'force_majeure'])->default('employer');
                $table->enum('status', ['active', 'resumed', 'terminated'])->default('active');
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // إنهاء العقد
        if (!Schema::hasTable('contract_terminations')) {
            Schema::create('contract_terminations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
                $table->date('termination_date');
                $table->date('effective_date');
                $table->enum('termination_reason', ['completion', 'mutual', 'breach', 'convenience', 'force_majeure', 'insolvency'])->default('completion');
                $table->text('reason_details')->nullable();
                $table->enum('initiated_by', ['employer', 'contractor', 'mutual'])->default('employer');
                $table->decimal('work_completed_percent', 5, 2)->nullable();
                $table->decimal('settlement_amount', 15, 3)->nullable();
                $table->text('settlement_terms')->nullable();
                $table->enum('status', ['pending', 'approved', 'effective'])->default('pending');
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // تسليم العقد
        if (!Schema::hasTable('contract_handovers')) {
            Schema::create('contract_handovers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
                $table->enum('handover_type', ['provisional', 'final'])->default('provisional');
                $table->date('handover_date');
                $table->date('handover_requested_date')->nullable();
                $table->text('description')->nullable();
                $table->json('checklist')->nullable();
                $table->json('defects_list')->nullable();
                $table->date('defects_liability_start')->nullable();
                $table->date('defects_liability_end')->nullable();
                $table->enum('status', ['requested', 'inspection', 'pending_defects', 'accepted', 'rejected'])->default('requested');
                $table->text('notes')->nullable();
                $table->foreignId('accepted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('accepted_at')->nullable();
                $table->timestamps();
            });
        }

        // فترة الصيانة
        if (!Schema::hasTable('defects_liabilities')) {
            Schema::create('defects_liabilities', function (Blueprint $table) {
                $table->id();
                $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
                $table->foreignId('handover_id')->nullable()->constrained('contract_handovers')->nullOnDelete();
                $table->date('start_date');
                $table->date('end_date');
                $table->integer('period_months');
                $table->decimal('retention_amount', 15, 3)->nullable();
                $table->enum('status', ['active', 'extended', 'completed'])->default('active');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // الاستلام النهائي
        if (!Schema::hasTable('final_acceptances')) {
            Schema::create('final_acceptances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
                $table->foreignId('defects_liability_id')->nullable()->constrained('defects_liabilities')->nullOnDelete();
                $table->date('certificate_date');
                $table->date('inspection_date')->nullable();
                $table->json('inspection_committee')->nullable();
                $table->boolean('all_defects_rectified')->default(false);
                $table->decimal('final_contract_value', 15, 3)->nullable();
                $table->decimal('retention_released', 15, 3)->nullable();
                $table->enum('status', ['pending', 'issued', 'contested'])->default('pending');
                $table->text('notes')->nullable();
                $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // مراسلات العقد
        if (!Schema::hasTable('contract_correspondences')) {
            Schema::create('contract_correspondences', function (Blueprint $table) {
                $table->id();
                $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
                $table->string('reference_number');
                $table->enum('type', ['letter', 'notice', 'claim', 'instruction', 'response', 'memo', 'other'])->default('letter');
                $table->enum('direction', ['incoming', 'outgoing'])->default('outgoing');
                $table->date('correspondence_date');
                $table->string('subject');
                $table->text('content')->nullable();
                $table->string('sender')->nullable();
                $table->string('recipient')->nullable();
                $table->date('response_required_by')->nullable();
                $table->boolean('response_received')->default(false);
                $table->date('response_date')->nullable();
                $table->string('file_path')->nullable();
                $table->foreignId('parent_id')->nullable()->constrained('contract_correspondences')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // تمديد الوقت
        if (!Schema::hasTable('extension_of_times')) {
            Schema::create('extension_of_times', function (Blueprint $table) {
                $table->id();
                $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
                $table->string('eot_number');
                $table->date('submission_date');
                $table->date('event_start_date');
                $table->date('event_end_date')->nullable();
                $table->integer('days_requested');
                $table->integer('days_approved')->nullable();
                $table->text('event_description');
                $table->enum('cause_type', ['weather', 'variations', 'unforeseen', 'employer_delay', 'other'])->default('other');
                $table->text('justification')->nullable();
                $table->date('original_completion_date');
                $table->date('new_completion_date')->nullable();
                $table->enum('status', ['submitted', 'under_review', 'approved', 'partially_approved', 'rejected'])->default('submitted');
                $table->text('review_notes')->nullable();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();
            });
        }

        // التعويضات المقطوعة
        if (!Schema::hasTable('liquidated_damages')) {
            Schema::create('liquidated_damages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
                $table->date('calculation_date');
                $table->date('original_completion');
                $table->date('actual_completion')->nullable();
                $table->integer('delay_days');
                $table->decimal('daily_rate', 15, 3);
                $table->decimal('max_percentage', 5, 2)->default(10);
                $table->decimal('calculated_amount', 15, 3);
                $table->decimal('capped_amount', 15, 3);
                $table->decimal('applied_amount', 15, 3)->nullable();
                $table->integer('approved_eot_days')->default(0);
                $table->enum('status', ['calculated', 'applied', 'waived', 'disputed'])->default('calculated');
                $table->text('notes')->nullable();
                $table->foreignId('calculated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('liquidated_damages');
        Schema::dropIfExists('extension_of_times');
        Schema::dropIfExists('contract_correspondences');
        Schema::dropIfExists('final_acceptances');
        Schema::dropIfExists('defects_liabilities');
        Schema::dropIfExists('contract_handovers');
        Schema::dropIfExists('contract_terminations');
        Schema::dropIfExists('contract_suspensions');
        Schema::dropIfExists('contract_amendments');
        Schema::dropIfExists('contract_bonuses');
        Schema::dropIfExists('contract_penalties');
        Schema::dropIfExists('contract_payment_schedules');
        Schema::dropIfExists('contract_milestones');
    }
};
