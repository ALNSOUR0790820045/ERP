<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Signature Requests (if not exists)
        if (!Schema::hasTable('signature_requests')) {
            Schema::create('signature_requests', function (Blueprint $table) {
                $table->id();
                $table->morphs('signable');
                $table->foreignId('requester_id')->constrained('users');
                $table->foreignId('signer_id')->constrained('users');
                $table->integer('order')->default(1);
                $table->string('status')->default('pending');
                $table->string('role')->nullable();
                $table->text('message')->nullable();
                $table->date('due_date')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('viewed_at')->nullable();
                $table->timestamp('signed_at')->nullable();
                $table->text('decline_reason')->nullable();
                $table->string('access_token')->nullable();
                $table->integer('reminder_count')->default(0);
                $table->timestamp('last_reminder_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->index(['status', 'signer_id']);
            });
        }

        // 2. Document OCR Results
        if (!Schema::hasTable('document_ocr_results')) {
            Schema::create('document_ocr_results', function (Blueprint $table) {
                $table->id();
                $table->foreignId('document_id')->nullable()->constrained()->cascadeOnDelete();
                $table->foreignId('revision_id')->nullable()->constrained('document_revisions');
                $table->string('file_path');
                $table->string('ocr_engine')->default('tesseract');
                $table->string('language')->default('ar');
                $table->longText('extracted_text')->nullable();
                $table->json('text_blocks')->nullable();
                $table->json('tables')->nullable();
                $table->json('key_value_pairs')->nullable();
                $table->decimal('confidence_score', 5, 2)->nullable();
                $table->string('status')->default('pending');
                $table->text('error_message')->nullable();
                $table->integer('pages_processed')->default(0);
                $table->integer('total_pages')->default(0);
                $table->integer('processing_time_ms')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->index(['document_id', 'status']);
            });
        }

        // 3. AI Document Classifications
        if (!Schema::hasTable('document_classifications')) {
            Schema::create('document_classifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('document_id')->constrained()->cascadeOnDelete();
                $table->string('ai_model')->default('gpt-4');
                $table->string('predicted_category')->nullable();
                $table->string('predicted_type')->nullable();
                $table->string('predicted_discipline')->nullable();
                $table->json('predicted_tags')->nullable();
                $table->json('predicted_metadata')->nullable();
                $table->decimal('confidence_score', 5, 2)->nullable();
                $table->json('all_predictions')->nullable();
                $table->string('status')->default('pending');
                $table->boolean('is_accepted')->nullable();
                $table->foreignId('reviewed_by')->nullable()->constrained('users');
                $table->timestamp('reviewed_at')->nullable();
                $table->text('review_notes')->nullable();
                $table->json('extracted_entities')->nullable();
                $table->text('summary')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        // 4. Document Full Text Search Index
        if (!Schema::hasTable('document_search_index')) {
            Schema::create('document_search_index', function (Blueprint $table) {
                $table->id();
                $table->foreignId('document_id')->constrained()->cascadeOnDelete();
                $table->foreignId('revision_id')->nullable()->constrained('document_revisions');
                $table->longText('content')->nullable();
                $table->text('title_text')->nullable();
                $table->text('metadata_text')->nullable();
                $table->json('keywords')->nullable();
                $table->json('entities')->nullable();
                $table->timestamp('indexed_at');
                $table->string('index_status')->default('active');
                $table->timestamps();
            });
        }

        // 5. Transmittal Acknowledgments
        if (!Schema::hasTable('transmittal_acknowledgments')) {
            Schema::create('transmittal_acknowledgments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('transmittal_id')->constrained()->cascadeOnDelete();
                $table->foreignId('recipient_id')->nullable()->constrained('users');
                $table->string('recipient_email')->nullable();
                $table->string('recipient_name')->nullable();
                $table->string('status')->default('pending');
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('received_at')->nullable();
                $table->timestamp('acknowledged_at')->nullable();
                $table->text('acknowledgment_notes')->nullable();
                $table->string('acknowledgment_method')->nullable();
                $table->string('ip_address')->nullable();
                $table->foreignId('signature_id')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        // 6. RFI Escalations
        if (!Schema::hasTable('rfi_escalations')) {
            Schema::create('rfi_escalations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('rfi_id')->constrained('rfis')->cascadeOnDelete();
                $table->integer('escalation_level')->default(1);
                $table->string('escalation_reason');
                $table->foreignId('escalated_from')->nullable()->constrained('users');
                $table->foreignId('escalated_to')->constrained('users');
                $table->text('escalation_notes')->nullable();
                $table->date('new_due_date')->nullable();
                $table->string('status')->default('active');
                $table->timestamp('escalated_at');
                $table->timestamp('resolved_at')->nullable();
                $table->text('resolution_notes')->nullable();
                $table->boolean('is_auto')->default(false);
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        // 7. Offline Sync Queue
        if (!Schema::hasTable('offline_sync_queue')) {
            Schema::create('offline_sync_queue', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('device_id');
                $table->string('sync_type');
                $table->morphs('syncable');
                $table->string('action');
                $table->json('local_data')->nullable();
                $table->json('server_data')->nullable();
                $table->string('status')->default('pending');
                $table->text('conflict_resolution')->nullable();
                $table->integer('retry_count')->default(0);
                $table->timestamp('queued_at');
                $table->timestamp('synced_at')->nullable();
                $table->text('error_message')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'device_id', 'status']);
            });
        }

        // 8. Document Analytics
        if (!Schema::hasTable('document_analytics')) {
            Schema::create('document_analytics', function (Blueprint $table) {
                $table->id();
                $table->foreignId('document_id')->nullable()->constrained()->cascadeOnDelete();
                $table->foreignId('project_id')->nullable()->constrained();
                $table->string('metric_type');
                $table->string('period_type')->default('daily');
                $table->date('period_date');
                $table->integer('count')->default(0);
                $table->decimal('value', 15, 2)->nullable();
                $table->json('breakdown')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->index(['project_id', 'metric_type', 'period_date']);
            });
        }

        // Add missing columns to documents table
        if (Schema::hasTable('documents')) {
            if (!Schema::hasColumn('documents', 'is_locked')) {
                Schema::table('documents', function (Blueprint $table) {
                    $table->boolean('is_locked')->default(false)->after('is_controlled');
                    $table->foreignId('locked_by')->nullable()->after('is_locked');
                    $table->timestamp('locked_at')->nullable()->after('locked_by');
                    $table->string('lock_type')->nullable()->after('locked_at');
                });
            }
            if (!Schema::hasColumn('documents', 'requires_signature')) {
                Schema::table('documents', function (Blueprint $table) {
                    $table->boolean('requires_signature')->default(false);
                    $table->integer('signatures_required')->default(0);
                    $table->integer('signatures_collected')->default(0);
                });
            }
            if (!Schema::hasColumn('documents', 'is_searchable')) {
                Schema::table('documents', function (Blueprint $table) {
                    $table->boolean('is_searchable')->default(true);
                    $table->boolean('ocr_processed')->default(false);
                    $table->boolean('ai_classified')->default(false);
                });
            }
        }

        // Add missing columns to rfis table
        if (Schema::hasTable('rfis') && !Schema::hasColumn('rfis', 'auto_escalate')) {
            Schema::table('rfis', function (Blueprint $table) {
                $table->boolean('auto_escalate')->default(true);
                $table->integer('escalation_days')->default(3);
                $table->integer('current_escalation_level')->default(0);
                $table->timestamp('last_escalated_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_analytics');
        Schema::dropIfExists('offline_sync_queue');
        Schema::dropIfExists('rfi_escalations');
        Schema::dropIfExists('transmittal_acknowledgments');
        Schema::dropIfExists('document_search_index');
        Schema::dropIfExists('document_classifications');
        Schema::dropIfExists('document_ocr_results');
        Schema::dropIfExists('signature_requests');
    }
};
