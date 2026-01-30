<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add column only if it doesn't exist
     */
    private function addColumnIfNotExists(string $table, string $column, callable $definition): void
    {
        if (Schema::hasTable($table) && !Schema::hasColumn($table, $column)) {
            Schema::table($table, function (Blueprint $t) use ($definition) {
                $definition($t);
            });
        }
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // tender_competitors - إضافة tender_id
        $this->addColumnIfNotExists('tender_competitors', 'tender_id', fn($t) => 
            $t->foreignId('tender_id')->nullable()->after('id')->constrained()->cascadeOnDelete()
        );
        $this->addColumnIfNotExists('tender_competitors', 'registration_number', fn($t) => 
            $t->string('registration_number')->nullable()
        );
        $this->addColumnIfNotExists('tender_competitors', 'company_size', fn($t) => 
            $t->string('company_size')->nullable()
        );
        $this->addColumnIfNotExists('tender_competitors', 'classification', fn($t) => 
            $t->string('classification')->nullable()
        );
        $this->addColumnIfNotExists('tender_competitors', 'submitted_price', fn($t) => 
            $t->decimal('submitted_price', 18, 3)->nullable()
        );
        $this->addColumnIfNotExists('tender_competitors', 'technical_score', fn($t) => 
            $t->decimal('technical_score', 5, 2)->nullable()
        );
        $this->addColumnIfNotExists('tender_competitors', 'financial_score', fn($t) => 
            $t->decimal('financial_score', 5, 2)->nullable()
        );
        $this->addColumnIfNotExists('tender_competitors', 'discount_percentage', fn($t) => 
            $t->decimal('discount_percentage', 5, 2)->nullable()
        );
        $this->addColumnIfNotExists('tender_competitors', 'contact_person', fn($t) => 
            $t->string('contact_person')->nullable()
        );
        $this->addColumnIfNotExists('tender_competitors', 'contact_phone', fn($t) => 
            $t->string('contact_phone')->nullable()
        );
        $this->addColumnIfNotExists('tender_competitors', 'strengths', fn($t) => 
            $t->text('strengths')->nullable()
        );
        $this->addColumnIfNotExists('tender_competitors', 'weaknesses', fn($t) => 
            $t->text('weaknesses')->nullable()
        );
        $this->addColumnIfNotExists('tender_competitors', 'disqualification_reason', fn($t) => 
            $t->text('disqualification_reason')->nullable()
        );
        $this->addColumnIfNotExists('tender_competitors', 'status', fn($t) => 
            $t->string('status')->default('registered')
        );

        // tender_site_visits
        $this->addColumnIfNotExists('tender_site_visits', 'coordinates', fn($t) => 
            $t->string('coordinates')->nullable()
        );
        $this->addColumnIfNotExists('tender_site_visits', 'meeting_point', fn($t) => 
            $t->text('meeting_point')->nullable()
        );
        $this->addColumnIfNotExists('tender_site_visits', 'contact_person', fn($t) => 
            $t->string('contact_person')->nullable()
        );
        $this->addColumnIfNotExists('tender_site_visits', 'contact_phone', fn($t) => 
            $t->string('contact_phone')->nullable()
        );
        $this->addColumnIfNotExists('tender_site_visits', 'contact_email', fn($t) => 
            $t->string('contact_email')->nullable()
        );
        $this->addColumnIfNotExists('tender_site_visits', 'report_file', fn($t) => 
            $t->string('report_file')->nullable()
        );
        $this->addColumnIfNotExists('tender_site_visits', 'concerns', fn($t) => 
            $t->text('concerns')->nullable()
        );

        // tender_clarifications
        $this->addColumnIfNotExists('tender_clarifications', 'priority', fn($t) => 
            $t->string('priority')->default('medium')
        );
        $this->addColumnIfNotExists('tender_clarifications', 'question_type', fn($t) => 
            $t->string('question_type')->default('technical')
        );
        $this->addColumnIfNotExists('tender_clarifications', 'reference', fn($t) => 
            $t->string('reference')->nullable()
        );
        $this->addColumnIfNotExists('tender_clarifications', 'submitted_date', fn($t) => 
            $t->date('submitted_date')->nullable()
        );
        $this->addColumnIfNotExists('tender_clarifications', 'question_attachments', fn($t) => 
            $t->json('question_attachments')->nullable()
        );
        $this->addColumnIfNotExists('tender_clarifications', 'answer_attachments', fn($t) => 
            $t->json('answer_attachments')->nullable()
        );
        $this->addColumnIfNotExists('tender_clarifications', 'impact', fn($t) => 
            $t->string('impact')->nullable()
        );

        // tender_documents
        $this->addColumnIfNotExists('tender_documents', 'document_number', fn($t) => 
            $t->string('document_number')->nullable()
        );
        $this->addColumnIfNotExists('tender_documents', 'category', fn($t) => 
            $t->string('category')->default('received')
        );
        $this->addColumnIfNotExists('tender_documents', 'issue_date', fn($t) => 
            $t->date('issue_date')->nullable()
        );
        $this->addColumnIfNotExists('tender_documents', 'received_date', fn($t) => 
            $t->date('received_date')->nullable()
        );
        $this->addColumnIfNotExists('tender_documents', 'status', fn($t) => 
            $t->string('status')->default('active')
        );
        $this->addColumnIfNotExists('tender_documents', 'is_confidential', fn($t) => 
            $t->boolean('is_confidential')->default(false)
        );
        $this->addColumnIfNotExists('tender_documents', 'requires_response', fn($t) => 
            $t->boolean('requires_response')->default(false)
        );

        // tender_bonds
        $this->addColumnIfNotExists('tender_bonds', 'request_date', fn($t) => 
            $t->date('request_date')->nullable()
        );
        $this->addColumnIfNotExists('tender_bonds', 'percentage', fn($t) => 
            $t->decimal('percentage', 5, 2)->nullable()
        );
        $this->addColumnIfNotExists('tender_bonds', 'is_extended', fn($t) => 
            $t->boolean('is_extended')->default(false)
        );
        $this->addColumnIfNotExists('tender_bonds', 'extension_date', fn($t) => 
            $t->date('extension_date')->nullable()
        );
        $this->addColumnIfNotExists('tender_bonds', 'new_expiry_date', fn($t) => 
            $t->date('new_expiry_date')->nullable()
        );
        $this->addColumnIfNotExists('tender_bonds', 'extension_reason', fn($t) => 
            $t->text('extension_reason')->nullable()
        );
        $this->addColumnIfNotExists('tender_bonds', 'bank_fees', fn($t) => 
            $t->decimal('bank_fees', 12, 3)->nullable()
        );
        $this->addColumnIfNotExists('tender_bonds', 'bank_commission_rate', fn($t) => 
            $t->decimal('bank_commission_rate', 5, 2)->nullable()
        );
        $this->addColumnIfNotExists('tender_bonds', 'document_path', fn($t) => 
            $t->string('document_path')->nullable()
        );

        // tender_stage_logs
        $this->addColumnIfNotExists('tender_stage_logs', 'action', fn($t) => 
            $t->string('action')->default('note')
        );
        $this->addColumnIfNotExists('tender_stage_logs', 'title', fn($t) => 
            $t->string('title')->nullable()
        );
        $this->addColumnIfNotExists('tender_stage_logs', 'status', fn($t) => 
            $t->string('status')->default('info')
        );

        // boq_items
        $this->addColumnIfNotExists('boq_items', 'category', fn($t) => 
            $t->string('category')->nullable()
        );
        $this->addColumnIfNotExists('boq_items', 'parent_id', fn($t) => 
            $t->foreignId('parent_id')->nullable()->constrained('boq_items')->nullOnDelete()
        );
        $this->addColumnIfNotExists('boq_items', 'description_en', fn($t) => 
            $t->text('description_en')->nullable()
        );
        $this->addColumnIfNotExists('boq_items', 'specifications', fn($t) => 
            $t->text('specifications')->nullable()
        );
        $this->addColumnIfNotExists('boq_items', 'material_cost', fn($t) => 
            $t->decimal('material_cost', 18, 3)->nullable()
        );
        $this->addColumnIfNotExists('boq_items', 'labor_cost', fn($t) => 
            $t->decimal('labor_cost', 18, 3)->nullable()
        );
        $this->addColumnIfNotExists('boq_items', 'equipment_cost', fn($t) => 
            $t->decimal('equipment_cost', 18, 3)->nullable()
        );
        $this->addColumnIfNotExists('boq_items', 'overhead_percentage', fn($t) => 
            $t->decimal('overhead_percentage', 5, 2)->nullable()
        );
        $this->addColumnIfNotExists('boq_items', 'sort_order', fn($t) => 
            $t->integer('sort_order')->nullable()
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // لا نحتاج لحذف الأعمدة
    }
};
