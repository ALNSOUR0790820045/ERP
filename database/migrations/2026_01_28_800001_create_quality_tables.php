<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // قوائم فحص الجودة
        Schema::create('quality_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            
            $table->string('code', 30)->unique();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->string('checklist_type', 30)->nullable();
            $table->text('description')->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // بنود قائمة الفحص
        Schema::create('quality_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_id')->constrained('quality_checklists')->cascadeOnDelete();
            
            $table->integer('item_number');
            $table->string('item_description');
            $table->string('acceptance_criteria')->nullable();
            $table->string('inspection_method')->nullable();
            $table->boolean('is_mandatory')->default(true);
            
            $table->timestamps();
        });

        // عمليات التفتيش
        Schema::create('quality_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained();
            $table->foreignId('checklist_id')->nullable()->constrained('quality_checklists');
            
            $table->string('inspection_number', 50)->unique();
            $table->date('inspection_date');
            $table->string('inspection_type', 30);
            $table->string('location')->nullable();
            $table->string('work_activity')->nullable();
            
            $table->string('status', 30)->default('pending');
            $table->string('result', 30)->nullable(); // passed, failed, conditional
            
            $table->foreignId('inspector_id')->nullable()->constrained('users');
            $table->foreignId('witnessed_by')->nullable()->constrained('users');
            
            $table->text('findings')->nullable();
            $table->text('recommendations')->nullable();
            
            $table->timestamps();
        });

        // نتائج بنود التفتيش
        Schema::create('quality_inspection_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained('quality_inspections')->cascadeOnDelete();
            $table->foreignId('checklist_item_id')->nullable()->constrained('quality_checklist_items');
            
            $table->string('item_description');
            $table->string('result', 30); // pass, fail, na
            $table->text('remarks')->nullable();
            
            $table->timestamps();
        });

        // عدم المطابقة (NCRs)
        Schema::create('non_conformance_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained();
            $table->foreignId('inspection_id')->nullable()->constrained('quality_inspections');
            
            $table->string('ncr_number', 50)->unique();
            $table->date('ncr_date');
            $table->string('category', 30)->nullable();
            $table->string('severity', 20)->default('minor'); // minor, major, critical
            
            $table->text('description');
            $table->text('root_cause')->nullable();
            $table->text('immediate_action')->nullable();
            $table->text('corrective_action')->nullable();
            $table->text('preventive_action')->nullable();
            
            $table->string('location')->nullable();
            $table->string('work_activity')->nullable();
            $table->string('responsible_party')->nullable();
            
            $table->string('status', 30)->default('open');
            $table->date('target_close_date')->nullable();
            $table->date('actual_close_date')->nullable();
            
            $table->foreignId('raised_by')->nullable()->constrained('users');
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->foreignId('closed_by')->nullable()->constrained('users');
            
            $table->timestamps();
        });

        // اختبارات المواد
        Schema::create('material_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained();
            $table->foreignId('material_id')->nullable()->constrained();
            
            $table->string('test_number', 50)->unique();
            $table->date('test_date');
            $table->string('test_type', 50);
            $table->string('sample_id', 50)->nullable();
            
            $table->string('testing_lab')->nullable();
            $table->date('sample_date')->nullable();
            $table->string('sample_location')->nullable();
            
            $table->text('test_parameters')->nullable();
            $table->text('test_results')->nullable();
            $table->text('acceptance_criteria')->nullable();
            
            $table->string('result', 30)->nullable(); // pass, fail
            $table->text('remarks')->nullable();
            
            $table->string('status', 30)->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_tests');
        Schema::dropIfExists('non_conformance_reports');
        Schema::dropIfExists('quality_inspection_results');
        Schema::dropIfExists('quality_inspections');
        Schema::dropIfExists('quality_checklist_items');
        Schema::dropIfExists('quality_checklists');
    }
};
