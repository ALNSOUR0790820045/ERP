<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // فئات الوثائق
        Schema::create('document_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('document_categories');
            
            $table->string('code', 30)->unique();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->integer('level')->default(1);
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // الوثائق
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained();
            $table->foreignId('project_id')->nullable()->constrained();
            $table->foreignId('category_id')->nullable()->constrained('document_categories');
            
            $table->string('document_number', 50)->unique();
            $table->string('title');
            $table->text('description')->nullable();
            
            $table->string('document_type', 30);
            $table->string('discipline', 30)->nullable();
            $table->string('originator', 50)->nullable();
            
            $table->string('current_revision', 20)->default('0');
            $table->string('status', 30)->default('draft');
            
            $table->date('issue_date')->nullable();
            $table->date('effective_date')->nullable();
            $table->date('expiry_date')->nullable();
            
            $table->string('confidentiality', 20)->default('internal');
            $table->boolean('is_controlled')->default(false);
            
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            
            $table->timestamps();
            $table->softDeletes();
        });

        // مراجعات الوثائق
        Schema::create('document_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            
            $table->string('revision_number', 20);
            $table->date('revision_date');
            $table->text('revision_reason')->nullable();
            $table->text('changes_description')->nullable();
            
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_type', 20)->nullable();
            $table->bigInteger('file_size')->nullable();
            
            $table->string('status', 30)->default('draft');
            $table->foreignId('prepared_by')->nullable()->constrained('users');
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        // توزيع الوثائق
        Schema::create('document_distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('revision_id')->nullable()->constrained('document_revisions');
            
            $table->string('distribution_type', 30)->default('information');
            $table->string('recipient_type', 50)->nullable();
            $table->unsignedBigInteger('recipient_id')->nullable();
            $table->string('recipient_name')->nullable();
            $table->string('recipient_email')->nullable();
            
            $table->date('distribution_date');
            $table->string('distribution_method', 30)->default('email');
            $table->integer('copies_count')->default(1);
            
            $table->boolean('acknowledged')->default(false);
            $table->timestamp('acknowledged_at')->nullable();
            
            $table->timestamps();
        });

        // تعليقات/مراجعات الوثائق
        Schema::create('document_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('revision_id')->nullable()->constrained('document_revisions');
            
            $table->text('comment');
            $table->string('comment_type', 30)->default('general');
            $table->string('status', 30)->default('open');
            
            $table->foreignId('commented_by')->nullable()->constrained('users');
            $table->foreignId('resolved_by')->nullable()->constrained('users');
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution')->nullable();
            
            $table->timestamps();
        });

        // الإرساليات (Transmittals)
        Schema::create('transmittals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained();
            
            $table->string('transmittal_number', 50)->unique();
            $table->date('transmittal_date');
            $table->string('transmittal_type', 30)->default('outgoing');
            
            $table->string('from_party');
            $table->string('to_party');
            $table->string('attention')->nullable();
            
            $table->text('subject')->nullable();
            $table->text('purpose')->nullable();
            $table->text('remarks')->nullable();
            
            $table->string('delivery_method', 30)->nullable();
            $table->string('status', 30)->default('draft');
            
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        // بنود الإرسالية
        Schema::create('transmittal_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transmittal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_id')->nullable()->constrained();
            $table->foreignId('revision_id')->nullable()->constrained('document_revisions');
            
            $table->integer('item_number');
            $table->string('document_number', 100)->nullable();
            $table->string('document_title')->nullable();
            $table->string('revision', 20)->nullable();
            $table->integer('copies')->default(1);
            $table->text('remarks')->nullable();
            
            $table->timestamps();
        });

        // RFI - طلبات المعلومات
        Schema::create('rfis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained();
            
            $table->string('rfi_number', 50)->unique();
            $table->date('rfi_date');
            $table->string('subject');
            $table->text('question');
            
            $table->string('discipline', 30)->nullable();
            $table->string('location')->nullable();
            $table->string('priority', 20)->default('normal');
            
            $table->date('required_date')->nullable();
            $table->date('response_date')->nullable();
            $table->text('response')->nullable();
            
            $table->string('status', 30)->default('open');
            $table->foreignId('raised_by')->nullable()->constrained('users');
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->foreignId('responded_by')->nullable()->constrained('users');
            
            $table->timestamps();
        });

        // المراسلات
        Schema::create('correspondences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained();
            $table->foreignId('company_id')->nullable()->constrained();
            
            $table->string('reference_number', 50)->unique();
            $table->date('correspondence_date');
            $table->string('correspondence_type', 30); // letter, email, memo, notice
            $table->string('direction', 10); // incoming, outgoing
            
            $table->string('from_party');
            $table->string('to_party');
            $table->string('attention')->nullable();
            $table->string('cc')->nullable();
            
            $table->string('subject');
            $table->text('content')->nullable();
            
            $table->string('priority', 20)->default('normal');
            $table->string('status', 30)->default('open');
            $table->date('response_required_date')->nullable();
            $table->date('response_date')->nullable();
            
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('correspondences');
        Schema::dropIfExists('rfis');
        Schema::dropIfExists('transmittal_items');
        Schema::dropIfExists('transmittals');
        Schema::dropIfExists('document_comments');
        Schema::dropIfExists('document_distributions');
        Schema::dropIfExists('document_revisions');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('document_categories');
    }
};
