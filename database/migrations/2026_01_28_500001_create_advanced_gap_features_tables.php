<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // =====================================================
        // 1. المطابقة الثلاثية للمشتريات (3-Way Matching)
        // =====================================================
        
        if (!Schema::hasTable('three_way_matches')) {
            Schema::create('three_way_matches', function (Blueprint $table) {
                $table->id();
                $table->string('match_number')->unique();
                $table->foreignId('purchase_order_id')->constrained()->onDelete('cascade');
                $table->foreignId('goods_receipt_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('supplier_invoice_id')->nullable()->constrained()->onDelete('set null');
                $table->enum('match_status', [
                    'pending',           // في انتظار المطابقة
                    'partial_grn',       // استلام جزئي
                    'partial_invoice',   // فاتورة جزئية
                    'matched',           // مطابق
                    'variance',          // يوجد فروقات
                    'approved',          // معتمد
                    'rejected'           // مرفوض
                ])->default('pending');
                
                // ملخص المطابقة
                $table->decimal('po_total', 15, 2)->default(0);
                $table->decimal('grn_total', 15, 2)->default(0);
                $table->decimal('invoice_total', 15, 2)->default(0);
                $table->decimal('quantity_variance', 15, 3)->default(0);
                $table->decimal('price_variance', 15, 2)->default(0);
                $table->decimal('variance_percentage', 5, 2)->default(0);
                
                // حدود التسامح
                $table->decimal('tolerance_percentage', 5, 2)->default(0);
                $table->boolean('within_tolerance')->default(true);
                $table->boolean('auto_approved')->default(false);
                
                // الموافقات
                $table->foreignId('approved_by')->nullable()->constrained('users');
                $table->timestamp('approved_at')->nullable();
                $table->text('approval_notes')->nullable();
                $table->text('rejection_reason')->nullable();
                
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('three_way_match_items')) {
            Schema::create('three_way_match_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('three_way_match_id')->constrained()->onDelete('cascade');
                $table->foreignId('material_id')->nullable()->constrained();
                $table->string('item_description');
                
                // كميات وأسعار أمر الشراء
                $table->decimal('po_quantity', 15, 3)->default(0);
                $table->decimal('po_unit_price', 15, 2)->default(0);
                $table->decimal('po_total', 15, 2)->default(0);
                
                // كميات استلام البضاعة
                $table->decimal('grn_quantity', 15, 3)->default(0);
                $table->decimal('grn_total', 15, 2)->default(0);
                
                // كميات وأسعار الفاتورة
                $table->decimal('invoice_quantity', 15, 3)->default(0);
                $table->decimal('invoice_unit_price', 15, 2)->default(0);
                $table->decimal('invoice_total', 15, 2)->default(0);
                
                // الفروقات
                $table->decimal('quantity_variance', 15, 3)->default(0);
                $table->decimal('price_variance', 15, 2)->default(0);
                $table->enum('variance_type', ['none', 'over', 'under', 'price'])->default('none');
                $table->boolean('is_matched')->default(false);
                
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('matching_tolerance_rules')) {
            Schema::create('matching_tolerance_rules', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('name_ar')->nullable();
                $table->enum('rule_type', ['quantity', 'price', 'amount'])->default('amount');
                $table->decimal('tolerance_percentage', 5, 2)->default(0);
                $table->decimal('tolerance_amount', 15, 2)->nullable();
                $table->decimal('min_amount', 15, 2)->nullable();
                $table->decimal('max_amount', 15, 2)->nullable();
                $table->boolean('auto_approve_within_tolerance')->default(true);
                $table->boolean('is_active')->default(true);
                $table->integer('priority')->default(0);
                $table->timestamps();
            });
        }

        // =====================================================
        // 2. إدارة الائتمان المتقدمة (Credit Management)
        // =====================================================
        
        if (!Schema::hasTable('customer_credit_profiles')) {
            Schema::create('customer_credit_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_id')->constrained()->onDelete('cascade');
                
                // حدود الائتمان
                $table->decimal('credit_limit', 15, 2)->default(0);
                $table->decimal('current_balance', 15, 2)->default(0);
                $table->decimal('available_credit', 15, 2)->default(0);
                $table->decimal('overdue_amount', 15, 2)->default(0);
                
                // تصنيف ائتماني
                $table->enum('credit_rating', ['A', 'B', 'C', 'D', 'F'])->default('B');
                $table->integer('credit_score')->default(500);
                $table->date('last_score_update')->nullable();
                
                // شروط الدفع
                $table->integer('payment_terms_days')->default(30);
                $table->decimal('early_payment_discount', 5, 2)->default(0);
                $table->integer('discount_days')->default(0);
                
                // الحالة
                $table->enum('credit_status', [
                    'active',        // نشط
                    'on_hold',       // موقوف مؤقتاً
                    'blocked',       // محظور
                    'under_review'   // قيد المراجعة
                ])->default('active');
                $table->text('hold_reason')->nullable();
                $table->foreignId('held_by')->nullable()->constrained('users');
                $table->timestamp('held_at')->nullable();
                
                // تنبيهات
                $table->decimal('warning_threshold_percentage', 5, 2)->default(80);
                $table->boolean('notify_on_threshold')->default(true);
                $table->boolean('notify_on_overdue')->default(true);
                $table->boolean('auto_hold_on_limit')->default(false);
                
                // إحصائيات
                $table->integer('total_invoices')->default(0);
                $table->integer('paid_on_time')->default(0);
                $table->integer('paid_late')->default(0);
                $table->decimal('average_days_to_pay', 8, 2)->default(0);
                
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('credit_limit_changes')) {
            Schema::create('credit_limit_changes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_credit_profile_id')->constrained()->onDelete('cascade');
                $table->decimal('previous_limit', 15, 2);
                $table->decimal('new_limit', 15, 2);
                $table->string('change_reason');
                $table->enum('change_type', ['increase', 'decrease', 'initial', 'review']);
                $table->foreignId('changed_by')->constrained('users');
                $table->foreignId('approved_by')->nullable()->constrained('users');
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('credit_reviews')) {
            Schema::create('credit_reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_credit_profile_id')->constrained()->onDelete('cascade');
                $table->date('review_date');
                $table->string('previous_rating', 1);
                $table->string('new_rating', 1);
                $table->integer('previous_score');
                $table->integer('new_score');
                $table->json('score_factors')->nullable();
                $table->text('review_notes')->nullable();
                $table->foreignId('reviewed_by')->constrained('users');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('credit_alerts')) {
            Schema::create('credit_alerts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_credit_profile_id')->constrained()->onDelete('cascade');
                $table->enum('alert_type', [
                    'threshold_warning',  // تحذير اقتراب الحد
                    'limit_exceeded',     // تجاوز الحد
                    'overdue_payment',    // تأخر السداد
                    'rating_downgrade',   // تخفيض التصنيف
                    'payment_received',   // استلام دفعة
                    'credit_review_due'   // موعد المراجعة
                ]);
                $table->string('alert_message');
                $table->decimal('related_amount', 15, 2)->nullable();
                $table->boolean('is_read')->default(false);
                $table->boolean('is_actioned')->default(false);
                $table->foreignId('actioned_by')->nullable()->constrained('users');
                $table->timestamp('actioned_at')->nullable();
                $table->text('action_taken')->nullable();
                $table->timestamps();
            });
        }

        // =====================================================
        // 3. الفوترة الإلكترونية JoFotara
        // =====================================================
        
        if (!Schema::hasTable('jofotara_settings')) {
            Schema::create('jofotara_settings', function (Blueprint $table) {
                $table->id();
                $table->string('taxpayer_id')->comment('الرقم الضريبي');
                $table->string('activity_number')->comment('رقم النشاط');
                $table->string('api_key')->nullable();
                $table->string('api_secret')->nullable();
                $table->string('certificate_path')->nullable();
                $table->string('private_key_path')->nullable();
                $table->enum('environment', ['sandbox', 'production'])->default('sandbox');
                $table->string('api_base_url')->nullable();
                $table->boolean('is_active')->default(false);
                $table->timestamp('last_sync')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('electronic_invoices')) {
            Schema::create('electronic_invoices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
                
                // معلومات الفاتورة الإلكترونية
                $table->string('einvoice_number')->unique()->nullable();
                $table->string('uuid')->unique();
                $table->string('qr_code')->nullable();
                $table->text('qr_code_data')->nullable();
                
                // الحالة
                $table->enum('status', [
                    'draft',           // مسودة
                    'pending',         // في انتظار الإرسال
                    'submitted',       // تم الإرسال
                    'accepted',        // مقبول
                    'rejected',        // مرفوض
                    'cancelled'        // ملغي
                ])->default('draft');
                
                // التوقيع الرقمي
                $table->text('digital_signature')->nullable();
                $table->string('signature_hash')->nullable();
                $table->timestamp('signed_at')->nullable();
                
                // الاستجابة من JoFotara
                $table->string('jofotara_reference')->nullable();
                $table->json('submission_response')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->integer('retry_count')->default(0);
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('accepted_at')->nullable();
                
                // XML/JSON
                $table->longText('xml_content')->nullable();
                $table->longText('json_payload')->nullable();
                
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('electronic_invoice_logs')) {
            Schema::create('electronic_invoice_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('electronic_invoice_id')->constrained()->onDelete('cascade');
                $table->enum('action', ['created', 'signed', 'submitted', 'accepted', 'rejected', 'cancelled', 'retry']);
                $table->string('status_before')->nullable();
                $table->string('status_after')->nullable();
                $table->json('request_data')->nullable();
                $table->json('response_data')->nullable();
                $table->text('error_message')->nullable();
                $table->string('ip_address')->nullable();
                $table->foreignId('performed_by')->nullable()->constrained('users');
                $table->timestamps();
            });
        }

        // =====================================================
        // 4. تحليل What-If للمشاريع
        // =====================================================
        
        if (!Schema::hasTable('whatif_scenarios')) {
            Schema::create('whatif_scenarios', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained()->onDelete('cascade');
                $table->string('name');
                $table->string('name_ar')->nullable();
                $table->text('description')->nullable();
                $table->enum('scenario_type', [
                    'schedule',       // جدول زمني
                    'cost',           // تكلفة
                    'resource',       // موارد
                    'risk',           // مخاطر
                    'combined'        // مجمع
                ])->default('schedule');
                
                // الحالة
                $table->enum('status', ['draft', 'analyzing', 'completed', 'archived'])->default('draft');
                $table->boolean('is_baseline')->default(false);
                
                // نتائج التحليل
                $table->date('original_end_date')->nullable();
                $table->date('projected_end_date')->nullable();
                $table->integer('schedule_impact_days')->default(0);
                $table->decimal('original_cost', 15, 2)->default(0);
                $table->decimal('projected_cost', 15, 2)->default(0);
                $table->decimal('cost_impact', 15, 2)->default(0);
                $table->decimal('cost_impact_percentage', 8, 2)->default(0);
                
                // درجة الثقة
                $table->decimal('confidence_level', 5, 2)->default(0);
                $table->json('probability_distribution')->nullable();
                
                $table->foreignId('created_by')->constrained('users');
                $table->foreignId('analyzed_by')->nullable()->constrained('users');
                $table->timestamp('analyzed_at')->nullable();
                
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('whatif_assumptions')) {
            Schema::create('whatif_assumptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('whatif_scenario_id')->constrained()->onDelete('cascade');
                $table->enum('assumption_type', [
                    'activity_delay',        // تأخير نشاط
                    'activity_acceleration', // تسريع نشاط
                    'cost_increase',         // زيادة تكلفة
                    'cost_decrease',         // تخفيض تكلفة
                    'resource_unavailable',  // عدم توفر مورد
                    'resource_added',        // إضافة مورد
                    'scope_change',          // تغيير نطاق
                    'risk_occurrence',       // حدوث خطر
                    'weather_delay',         // تأخير طقس
                    'custom'                 // مخصص
                ]);
                
                // العنصر المتأثر
                $table->string('affected_entity_type')->nullable();
                $table->unsignedBigInteger('affected_entity_id')->nullable();
                $table->string('affected_entity_name')->nullable();
                
                // قيم التغيير
                $table->string('parameter_name');
                $table->decimal('original_value', 15, 2)->nullable();
                $table->decimal('assumed_value', 15, 2)->nullable();
                $table->string('value_unit')->nullable();
                $table->decimal('change_percentage', 8, 2)->nullable();
                
                // الاحتمالية
                $table->decimal('probability', 5, 2)->default(100);
                $table->text('justification')->nullable();
                
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('whatif_results')) {
            Schema::create('whatif_results', function (Blueprint $table) {
                $table->id();
                $table->foreignId('whatif_scenario_id')->constrained()->onDelete('cascade');
                $table->enum('result_type', ['schedule', 'cost', 'resource', 'kpi']);
                $table->string('metric_name');
                $table->string('metric_name_ar')->nullable();
                $table->decimal('baseline_value', 15, 2);
                $table->decimal('scenario_value', 15, 2);
                $table->decimal('variance', 15, 2);
                $table->decimal('variance_percentage', 8, 2);
                $table->enum('impact_level', ['positive', 'neutral', 'minor', 'moderate', 'severe'])->default('neutral');
                $table->json('breakdown')->nullable();
                $table->timestamps();
            });
        }

        // =====================================================
        // 5. تكامل BIM 4D/5D
        // =====================================================
        
        if (!Schema::hasTable('bim_projects')) {
            Schema::create('bim_projects', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained()->onDelete('cascade');
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('ifc_file_path')->nullable();
                $table->string('revit_file_path')->nullable();
                $table->string('model_version')->nullable();
                $table->json('model_metadata')->nullable();
                $table->decimal('total_elements', 10, 0)->default(0);
                $table->decimal('linked_elements', 10, 0)->default(0);
                $table->timestamp('last_sync')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('bim_elements')) {
            Schema::create('bim_elements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bim_project_id')->constrained()->onDelete('cascade');
                $table->string('element_id')->comment('IFC/Revit Element ID');
                $table->string('element_type')->comment('Wall, Floor, Column, etc.');
                $table->string('element_name')->nullable();
                $table->string('ifc_class')->nullable();
                $table->json('properties')->nullable();
                $table->json('geometry_data')->nullable();
                $table->string('level')->nullable();
                $table->string('zone')->nullable();
                
                // ربط مع BOQ
                $table->foreignId('boq_item_id')->nullable()->constrained()->onDelete('set null');
                
                // ربط مع الجدول الزمني (4D)
                $table->foreignId('gantt_task_id')->nullable()->constrained()->onDelete('set null');
                $table->date('planned_start')->nullable();
                $table->date('planned_end')->nullable();
                $table->date('actual_start')->nullable();
                $table->date('actual_end')->nullable();
                
                // ربط مع التكلفة (5D)
                $table->decimal('estimated_cost', 15, 2)->default(0);
                $table->decimal('actual_cost', 15, 2)->default(0);
                $table->decimal('quantity', 15, 3)->default(0);
                $table->string('unit')->nullable();
                
                // حالة التنفيذ
                $table->enum('status', [
                    'not_started',
                    'in_progress',
                    'completed',
                    'on_hold',
                    'cancelled'
                ])->default('not_started');
                $table->decimal('progress_percentage', 5, 2)->default(0);
                
                $table->timestamps();
                
                $table->unique(['bim_project_id', 'element_id']);
            });
        }

        if (!Schema::hasTable('bim_clash_detections')) {
            Schema::create('bim_clash_detections', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bim_project_id')->constrained()->onDelete('cascade');
                $table->string('clash_id')->unique();
                $table->foreignId('element_1_id')->constrained('bim_elements')->onDelete('cascade');
                $table->foreignId('element_2_id')->constrained('bim_elements')->onDelete('cascade');
                $table->enum('clash_type', ['hard', 'soft', 'clearance'])->default('hard');
                $table->enum('severity', ['critical', 'major', 'minor'])->default('minor');
                $table->json('clash_point')->nullable();
                $table->decimal('distance', 10, 3)->nullable();
                $table->enum('status', ['new', 'active', 'resolved', 'approved', 'ignored'])->default('new');
                $table->text('resolution_notes')->nullable();
                $table->foreignId('resolved_by')->nullable()->constrained('users');
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();
            });
        }

        // =====================================================
        // 6. Workflow متقدم
        // =====================================================
        
        if (!Schema::hasTable('workflow_templates')) {
            Schema::create('workflow_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('name_ar')->nullable();
                $table->text('description')->nullable();
                $table->string('entity_type')->comment('Invoice, PurchaseOrder, Contract, etc.');
                $table->enum('trigger_event', [
                    'on_create',
                    'on_update',
                    'on_status_change',
                    'on_amount_threshold',
                    'manual',
                    'scheduled'
                ])->default('on_create');
                $table->json('trigger_conditions')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('priority')->default(0);
                $table->integer('version')->default(1);
                $table->foreignId('created_by')->constrained('users');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('workflow_steps')) {
            Schema::create('workflow_steps', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workflow_template_id')->constrained()->onDelete('cascade');
                $table->integer('step_order');
                $table->string('name');
                $table->string('name_ar')->nullable();
                $table->enum('step_type', [
                    'approval',      // موافقة
                    'review',        // مراجعة
                    'notification',  // إشعار
                    'action',        // إجراء
                    'condition',     // شرط
                    'parallel',      // متوازي
                    'escalation'     // تصعيد
                ])->default('approval');
                
                // المسؤول عن الخطوة
                $table->enum('assignee_type', ['user', 'role', 'department', 'dynamic', 'supervisor'])->default('user');
                $table->unsignedBigInteger('assignee_id')->nullable();
                $table->string('dynamic_assignee_field')->nullable();
                
                // الشروط
                $table->json('conditions')->nullable();
                $table->json('required_fields')->nullable();
                
                // المهلة الزمنية
                $table->integer('due_days')->nullable();
                $table->integer('due_hours')->nullable();
                $table->boolean('auto_escalate')->default(false);
                $table->foreignId('escalate_to_user_id')->nullable()->constrained('users');
                
                // الإجراءات
                $table->json('on_approve_actions')->nullable();
                $table->json('on_reject_actions')->nullable();
                
                $table->boolean('is_optional')->default(false);
                $table->boolean('allow_delegation')->default(true);
                
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('workflow_instances')) {
            Schema::create('workflow_instances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workflow_template_id')->constrained()->onDelete('cascade');
                $table->string('entity_type');
                $table->unsignedBigInteger('entity_id');
                $table->string('entity_reference')->nullable();
                
                $table->enum('status', [
                    'pending',
                    'in_progress',
                    'completed',
                    'rejected',
                    'cancelled',
                    'on_hold'
                ])->default('pending');
                
                $table->integer('current_step_order')->default(1);
                $table->foreignId('current_step_id')->nullable()->constrained('workflow_steps');
                
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->integer('total_duration_hours')->nullable();
                
                $table->foreignId('initiated_by')->constrained('users');
                $table->foreignId('completed_by')->nullable()->constrained('users');
                
                $table->json('context_data')->nullable();
                
                $table->timestamps();
                $table->softDeletes();
                
                $table->index(['entity_type', 'entity_id']);
            });
        }

        if (!Schema::hasTable('workflow_step_executions')) {
            Schema::create('workflow_step_executions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workflow_instance_id')->constrained()->onDelete('cascade');
                $table->foreignId('workflow_step_id')->constrained()->onDelete('cascade');
                
                $table->enum('status', [
                    'pending',
                    'in_progress',
                    'approved',
                    'rejected',
                    'skipped',
                    'escalated',
                    'delegated'
                ])->default('pending');
                
                $table->foreignId('assigned_to')->constrained('users');
                $table->foreignId('actioned_by')->nullable()->constrained('users');
                $table->foreignId('delegated_to')->nullable()->constrained('users');
                $table->foreignId('escalated_to')->nullable()->constrained('users');
                
                $table->text('comments')->nullable();
                $table->json('form_data')->nullable();
                
                $table->timestamp('assigned_at')->nullable();
                $table->timestamp('due_at')->nullable();
                $table->timestamp('actioned_at')->nullable();
                $table->integer('duration_hours')->nullable();
                
                $table->timestamps();
            });
        }

        // =====================================================
        // 7. التعلم الآلي للتسعير
        // =====================================================
        
        if (!Schema::hasTable('pricing_models')) {
            Schema::create('pricing_models', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('name_ar')->nullable();
                $table->text('description')->nullable();
                $table->enum('model_type', [
                    'regression',        // انحدار
                    'random_forest',     // غابة عشوائية
                    'neural_network',    // شبكة عصبية
                    'ensemble',          // مجمع
                    'rule_based'         // قواعد
                ])->default('regression');
                
                $table->string('target_variable')->comment('unit_price, total_price, markup_percentage');
                $table->json('feature_columns')->nullable();
                $table->json('model_parameters')->nullable();
                $table->string('model_file_path')->nullable();
                
                // أداء النموذج
                $table->decimal('accuracy', 8, 4)->nullable();
                $table->decimal('r_squared', 8, 4)->nullable();
                $table->decimal('mean_absolute_error', 15, 2)->nullable();
                $table->decimal('mean_percentage_error', 8, 2)->nullable();
                
                $table->integer('training_samples')->default(0);
                $table->timestamp('last_trained_at')->nullable();
                $table->boolean('is_active')->default(false);
                $table->integer('version')->default(1);
                
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('pricing_predictions')) {
            Schema::create('pricing_predictions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pricing_model_id')->constrained()->onDelete('cascade');
                $table->foreignId('tender_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('boq_item_id')->nullable()->constrained()->onDelete('set null');
                
                // المدخلات
                $table->json('input_features');
                
                // التوقعات
                $table->decimal('predicted_unit_price', 15, 2);
                $table->decimal('predicted_total', 15, 2)->nullable();
                $table->decimal('confidence_score', 5, 2)->default(0);
                $table->decimal('lower_bound', 15, 2)->nullable();
                $table->decimal('upper_bound', 15, 2)->nullable();
                
                // القيمة الفعلية (للتعلم)
                $table->decimal('actual_unit_price', 15, 2)->nullable();
                $table->decimal('actual_total', 15, 2)->nullable();
                $table->decimal('prediction_error', 15, 2)->nullable();
                $table->decimal('error_percentage', 8, 2)->nullable();
                
                // التوصيات
                $table->json('price_factors')->nullable();
                $table->json('similar_items')->nullable();
                $table->text('recommendation_notes')->nullable();
                
                $table->foreignId('requested_by')->constrained('users');
                $table->boolean('is_accepted')->nullable();
                $table->text('feedback')->nullable();
                
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('historical_pricing_data')) {
            Schema::create('historical_pricing_data', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tender_id')->nullable()->constrained()->onDelete('set null');
                $table->string('item_code')->nullable();
                $table->string('item_description');
                $table->string('item_category')->nullable();
                $table->string('work_type')->nullable();
                
                // الموقع والمشروع
                $table->string('project_type')->nullable();
                $table->string('project_location')->nullable();
                $table->string('client_type')->nullable();
                
                // الكميات والأسعار
                $table->decimal('quantity', 15, 3);
                $table->string('unit');
                $table->decimal('unit_price', 15, 2);
                $table->decimal('total_price', 15, 2);
                $table->decimal('markup_percentage', 8, 2)->nullable();
                
                // عوامل التأثير
                $table->decimal('project_size', 15, 2)->nullable();
                $table->decimal('project_duration_months', 8, 2)->nullable();
                $table->integer('competition_level')->nullable();
                $table->decimal('material_cost_index', 8, 2)->nullable();
                $table->decimal('labor_cost_index', 8, 2)->nullable();
                
                // النتيجة
                $table->boolean('tender_won')->nullable();
                $table->date('tender_date');
                
                $table->boolean('is_training_data')->default(true);
                $table->timestamps();
                
                $table->index(['item_category', 'work_type']);
                $table->index('tender_date');
            });
        }
    }

    public function down(): void
    {
        // 7. التعلم الآلي للتسعير
        Schema::dropIfExists('historical_pricing_data');
        Schema::dropIfExists('pricing_predictions');
        Schema::dropIfExists('pricing_models');
        
        // 6. Workflow متقدم
        Schema::dropIfExists('workflow_step_executions');
        Schema::dropIfExists('workflow_instances');
        Schema::dropIfExists('workflow_steps');
        Schema::dropIfExists('workflow_templates');
        
        // 5. تكامل BIM 4D/5D
        Schema::dropIfExists('bim_clash_detections');
        Schema::dropIfExists('bim_elements');
        Schema::dropIfExists('bim_projects');
        
        // 4. تحليل What-If
        Schema::dropIfExists('whatif_results');
        Schema::dropIfExists('whatif_assumptions');
        Schema::dropIfExists('whatif_scenarios');
        
        // 3. الفوترة الإلكترونية
        Schema::dropIfExists('electronic_invoice_logs');
        Schema::dropIfExists('electronic_invoices');
        Schema::dropIfExists('jofotara_settings');
        
        // 2. إدارة الائتمان
        Schema::dropIfExists('credit_alerts');
        Schema::dropIfExists('credit_reviews');
        Schema::dropIfExists('credit_limit_changes');
        Schema::dropIfExists('customer_credit_profiles');
        
        // 1. المطابقة الثلاثية
        Schema::dropIfExists('matching_tolerance_rules');
        Schema::dropIfExists('three_way_match_items');
        Schema::dropIfExists('three_way_matches');
    }
};
