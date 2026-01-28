<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // فئات المخاطر
        Schema::create('hazard_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // تقييم المخاطر
        Schema::create('risk_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained();
            $table->foreignId('hazard_category_id')->nullable()->constrained();
            
            $table->string('assessment_number', 50)->unique();
            $table->date('assessment_date');
            $table->string('work_activity');
            $table->string('location')->nullable();
            
            $table->text('hazard_description');
            $table->text('potential_consequences')->nullable();
            
            $table->integer('likelihood')->default(1); // 1-5
            $table->integer('severity')->default(1); // 1-5
            $table->integer('risk_score')->default(1);
            $table->string('risk_level', 20)->default('low'); // low, medium, high, extreme
            
            $table->text('existing_controls')->nullable();
            $table->text('additional_controls')->nullable();
            
            $table->integer('residual_likelihood')->nullable();
            $table->integer('residual_severity')->nullable();
            $table->integer('residual_risk_score')->nullable();
            
            $table->string('status', 30)->default('draft');
            $table->foreignId('assessed_by')->nullable()->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            
            $table->timestamps();
        });

        // الحوادث
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained();
            
            $table->string('incident_number', 50)->unique();
            $table->datetime('incident_datetime');
            $table->string('incident_type', 30); // accident, near_miss, first_aid, property_damage
            $table->string('severity', 20)->default('minor');
            
            $table->string('location');
            $table->text('description');
            $table->text('immediate_actions')->nullable();
            
            $table->boolean('injury_occurred')->default(false);
            $table->integer('injuries_count')->default(0);
            $table->text('injury_details')->nullable();
            
            $table->boolean('property_damage')->default(false);
            $table->decimal('damage_cost', 12, 3)->nullable();
            $table->text('damage_details')->nullable();
            
            $table->boolean('work_stopped')->default(false);
            $table->decimal('lost_hours', 8, 2)->default(0);
            
            $table->text('root_cause')->nullable();
            $table->text('corrective_actions')->nullable();
            $table->text('preventive_actions')->nullable();
            
            $table->string('status', 30)->default('reported');
            $table->foreignId('reported_by')->nullable()->constrained('users');
            $table->foreignId('investigated_by')->nullable()->constrained('users');
            $table->date('closed_date')->nullable();
            
            $table->timestamps();
        });

        // أشخاص مرتبطين بالحادث
        Schema::create('incident_persons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained();
            
            $table->string('person_type', 30); // injured, witness, involved
            $table->string('person_name');
            $table->string('company_name')->nullable();
            $table->text('injury_description')->nullable();
            $table->text('statement')->nullable();
            
            $table->timestamps();
        });

        // تصاريح العمل
        Schema::create('work_permits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained();
            
            $table->string('permit_number', 50)->unique();
            $table->string('permit_type', 30); // hot_work, confined_space, excavation, height_work
            
            $table->datetime('start_datetime');
            $table->datetime('end_datetime');
            $table->string('location');
            $table->text('work_description');
            
            $table->text('hazards_identified')->nullable();
            $table->text('precautions')->nullable();
            $table->text('ppe_required')->nullable();
            $table->text('emergency_procedures')->nullable();
            
            $table->string('status', 30)->default('pending');
            $table->foreignId('requested_by')->nullable()->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            
            $table->foreignId('closed_by')->nullable()->constrained('users');
            $table->timestamp('closed_at')->nullable();
            $table->text('closure_remarks')->nullable();
            
            $table->timestamps();
        });

        // اجتماعات السلامة (Toolbox Talks)
        Schema::create('safety_meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained();
            
            $table->string('meeting_number', 50);
            $table->date('meeting_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            
            $table->string('meeting_type', 30);
            $table->string('topic');
            $table->text('agenda')->nullable();
            $table->text('discussion_points')->nullable();
            $table->text('action_items')->nullable();
            
            $table->integer('attendees_count')->default(0);
            $table->foreignId('conducted_by')->nullable()->constrained('users');
            
            $table->timestamps();
        });

        // حضور اجتماعات السلامة
        Schema::create('safety_meeting_attendees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained('safety_meetings')->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained();
            
            $table->string('attendee_name');
            $table->string('company')->nullable();
            $table->string('trade')->nullable();
            
            $table->timestamps();
        });

        // معدات الوقاية الشخصية
        Schema::create('ppe_items', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->string('category', 30)->nullable();
            $table->text('specifications')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // سجل توزيع معدات الوقاية
        Schema::create('ppe_distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained();
            $table->foreignId('employee_id')->constrained();
            $table->foreignId('ppe_item_id')->constrained();
            
            $table->date('issue_date');
            $table->integer('quantity')->default(1);
            $table->date('expiry_date')->nullable();
            $table->date('return_date')->nullable();
            
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ppe_distributions');
        Schema::dropIfExists('ppe_items');
        Schema::dropIfExists('safety_meeting_attendees');
        Schema::dropIfExists('safety_meetings');
        Schema::dropIfExists('work_permits');
        Schema::dropIfExists('incident_persons');
        Schema::dropIfExists('incidents');
        Schema::dropIfExists('risk_assessments');
        Schema::dropIfExists('hazard_categories');
    }
};
