<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * تحسينات الوحدات بناءً على مراجعة الوثائق التفصيلية
 */
return new class extends Migration
{
    public function up(): void
    {
        // ==================== 1. تحليل العطاءات ====================
        
        // جدول تحليل SWOT للعطاء
        if (!Schema::hasTable('tender_swot_analyses')) {
            Schema::create('tender_swot_analyses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tender_id')->constrained()->cascadeOnDelete();
                $table->enum('type', ['strength', 'weakness', 'opportunity', 'threat']);
                $table->text('description');
                $table->integer('impact_level')->default(3);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
        
        // جدول تحليل المخاطر للعطاء
        if (!Schema::hasTable('tender_risks')) {
            Schema::create('tender_risks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tender_id')->constrained()->cascadeOnDelete();
                $table->string('risk_name', 255);
                $table->text('description')->nullable();
                $table->enum('probability', ['low', 'medium', 'high', 'very_high']);
                $table->enum('impact', ['low', 'medium', 'high', 'very_high']);
                $table->text('mitigation')->nullable();
                $table->decimal('contingency_amount', 18, 3)->nullable();
                $table->timestamps();
            });
        }
        
        // جدول معايير Go/No-Go
        if (!Schema::hasTable('tender_decision_criteria')) {
            Schema::create('tender_decision_criteria', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tender_id')->constrained()->cascadeOnDelete();
                $table->string('criterion', 255);
                $table->decimal('weight', 5, 2);
                $table->integer('score');
                $table->decimal('weighted_score', 5, 2)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // ==================== 2. تحسينات العقود ====================
        
        // جدول مؤشرات تعديل الأسعار
        if (!Schema::hasTable('price_adjustment_indices')) {
            Schema::create('price_adjustment_indices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
                $table->string('index_name', 100);
                $table->string('index_code', 20);
                $table->decimal('weight', 5, 2);
                $table->decimal('base_value', 10, 4);
                $table->date('base_date');
                $table->text('source')->nullable();
                $table->timestamps();
            });
        }
        
        // جدول قراءات مؤشرات التعديل
        if (!Schema::hasTable('price_adjustment_readings')) {
            Schema::create('price_adjustment_readings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('price_adjustment_index_id')->constrained()->cascadeOnDelete();
                $table->date('reading_date');
                $table->decimal('value', 10, 4);
                $table->decimal('ratio', 8, 6)->nullable();
                $table->string('reference')->nullable();
                $table->timestamps();
            });
        }
        
        // جدول حسابات تعديل الأسعار للمستخلصات
        if (!Schema::hasTable('invoice_price_adjustments')) {
            Schema::create('invoice_price_adjustments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
                $table->date('calculation_date');
                $table->decimal('base_amount', 18, 3);
                $table->decimal('adjustment_factor', 8, 6);
                $table->decimal('adjustment_amount', 18, 3);
                $table->json('index_values')->nullable();
                $table->text('calculation_notes')->nullable();
                $table->timestamps();
            });
        }

        // ==================== 3. تحسينات المشاريع ====================
        
        // جدول Milestones المشروع
        if (!Schema::hasTable('project_milestones')) {
            Schema::create('project_milestones', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->foreignId('wbs_id')->nullable()->constrained('project_wbs')->nullOnDelete();
                $table->string('name', 255);
                $table->text('description')->nullable();
                $table->date('planned_date');
                $table->date('actual_date')->nullable();
                $table->decimal('weight', 5, 2)->nullable();
                $table->boolean('is_payment_milestone')->default(false);
                $table->decimal('payment_percentage', 5, 2)->nullable();
                $table->enum('status', ['pending', 'achieved', 'delayed', 'cancelled'])->default('pending');
                $table->timestamps();
            });
        }
        
        // جدول قيود الأنشطة
        if (!Schema::hasTable('wbs_constraints')) {
            Schema::create('wbs_constraints', function (Blueprint $table) {
                $table->id();
                $table->foreignId('wbs_id')->constrained('project_wbs')->cascadeOnDelete();
                $table->enum('constraint_type', ['ASAP', 'ALAP', 'MSO', 'MFO', 'SNET', 'SNLT', 'FNET', 'FNLT'])->default('ASAP');
                $table->date('constraint_date')->nullable();
                $table->text('reason')->nullable();
                $table->timestamps();
            });
        }

        // ==================== 4. تحسينات المستخلصات ====================
        
        // جدول المواد في الموقع
        if (!Schema::hasTable('invoice_materials_on_site')) {
            Schema::create('invoice_materials_on_site', function (Blueprint $table) {
                $table->id();
                $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
                $table->foreignId('material_id')->nullable()->constrained('materials')->nullOnDelete();
                $table->text('description');
                $table->decimal('quantity', 15, 3);
                $table->decimal('unit_price', 18, 3);
                $table->decimal('total_value', 18, 3);
                $table->decimal('claim_percentage', 5, 2)->default(80);
                $table->decimal('claimed_value', 18, 3);
                $table->string('delivery_note_ref')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
        
        // جدول تفاصيل الاستقطاعات
        if (!Schema::hasTable('invoice_deduction_details')) {
            Schema::create('invoice_deduction_details', function (Blueprint $table) {
                $table->id();
                $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
                $table->enum('deduction_type', [
                    'advance_recovery', 'retention', 'income_tax', 'sales_tax',
                    'contractor_union', 'liquidated_damages', 'backcharges', 'other'
                ]);
                $table->string('description', 255)->nullable();
                $table->decimal('rate', 8, 4)->nullable();
                $table->decimal('base_amount', 18, 3)->nullable();
                $table->decimal('amount', 18, 3);
                $table->decimal('previous_amount', 18, 3)->default(0);
                $table->decimal('cumulative_amount', 18, 3)->default(0);
                $table->decimal('limit_amount', 18, 3)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // ==================== 5. تحسينات المشتريات ====================
        
        // جدول طلبات عروض الأسعار RFQ
        if (!Schema::hasTable('rfqs')) {
            Schema::create('rfqs', function (Blueprint $table) {
                $table->id();
                $table->string('rfq_number', 50)->unique();
                $table->date('rfq_date');
                $table->string('subject', 500);
                $table->enum('rfq_type', ['rfq', 'rfp', 'rfi'])->default('rfq');
                $table->datetime('deadline');
                $table->integer('validity_days')->default(30);
                $table->date('delivery_required_date')->nullable();
                $table->string('delivery_location', 255)->nullable();
                $table->text('payment_terms')->nullable();
                $table->text('terms_conditions')->nullable();
                $table->enum('status', ['draft', 'sent', 'closed', 'awarded', 'cancelled'])->default('draft');
                $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }
        
        // جدول بنود RFQ
        if (!Schema::hasTable('rfq_items')) {
            Schema::create('rfq_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('rfq_id')->constrained()->cascadeOnDelete();
                $table->foreignId('purchase_request_item_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('material_id')->nullable()->constrained()->nullOnDelete();
                $table->text('description');
                $table->text('specifications')->nullable();
                $table->string('unit', 50);
                $table->decimal('quantity', 15, 3);
                $table->decimal('estimated_price', 18, 3)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
        
        // جدول الموردين المدعوين
        if (!Schema::hasTable('rfq_suppliers')) {
            Schema::create('rfq_suppliers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('rfq_id')->constrained()->cascadeOnDelete();
                $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
                $table->datetime('sent_at')->nullable();
                $table->datetime('responded_at')->nullable();
                $table->enum('response_status', ['pending', 'received', 'declined', 'no_response'])->default('pending');
                $table->timestamps();
            });
        }
        
        // جدول عروض الأسعار من الموردين
        if (!Schema::hasTable('supplier_quotes')) {
            Schema::create('supplier_quotes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('rfq_id')->constrained()->cascadeOnDelete();
                $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
                $table->string('quote_number', 100)->nullable();
                $table->date('quote_date');
                $table->date('validity_date')->nullable();
                $table->decimal('total_amount', 18, 3);
                $table->text('payment_terms')->nullable();
                $table->text('delivery_terms')->nullable();
                $table->integer('delivery_days')->nullable();
                $table->text('notes')->nullable();
                $table->boolean('is_selected')->default(false);
                $table->timestamps();
            });
        }
        
        // جدول بنود عروض الأسعار
        if (!Schema::hasTable('supplier_quote_items')) {
            Schema::create('supplier_quote_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('supplier_quote_id')->constrained()->cascadeOnDelete();
                $table->foreignId('rfq_item_id')->constrained()->cascadeOnDelete();
                $table->decimal('unit_price', 18, 3);
                $table->decimal('quantity', 15, 3);
                $table->decimal('total_price', 18, 3);
                $table->decimal('discount_percentage', 5, 2)->nullable();
                $table->decimal('discount_amount', 18, 3)->nullable();
                $table->decimal('net_price', 18, 3);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
        
        // جدول مقارنة العروض
        if (!Schema::hasTable('bid_comparisons')) {
            Schema::create('bid_comparisons', function (Blueprint $table) {
                $table->id();
                $table->foreignId('rfq_id')->constrained()->cascadeOnDelete();
                $table->string('comparison_number', 50);
                $table->date('comparison_date');
                $table->text('technical_evaluation')->nullable();
                $table->text('commercial_evaluation')->nullable();
                $table->text('recommendation')->nullable();
                $table->foreignId('recommended_supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->date('approved_date')->nullable();
                $table->enum('status', ['draft', 'pending', 'approved', 'rejected'])->default('draft');
                $table->timestamps();
            });
        }

        // ==================== 6. تحسينات المستودعات ====================
        
        // جدول مواقع التخزين داخل المستودع
        if (!Schema::hasTable('warehouse_locations')) {
            Schema::create('warehouse_locations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
                $table->string('location_code', 50)->unique();
                $table->string('zone', 20)->nullable();
                $table->string('aisle', 20)->nullable();
                $table->string('rack', 20)->nullable();
                $table->string('shelf', 20)->nullable();
                $table->string('bin', 20)->nullable();
                $table->decimal('capacity', 15, 3)->nullable();
                $table->decimal('current_usage', 15, 3)->default(0);
                $table->string('unit', 20)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
        
        // جدول أرصدة المواد حسب الموقع
        if (!Schema::hasTable('location_inventory')) {
            Schema::create('location_inventory', function (Blueprint $table) {
                $table->id();
                $table->foreignId('warehouse_location_id')->constrained()->cascadeOnDelete();
                $table->foreignId('material_id')->constrained()->cascadeOnDelete();
                $table->decimal('quantity', 15, 3);
                $table->string('lot_number', 100)->nullable();
                $table->date('expiry_date')->nullable();
                $table->timestamps();
            });
        }
        
        // جدول ABC Analysis
        if (!Schema::hasTable('material_abc_classifications')) {
            Schema::create('material_abc_classifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('material_id')->constrained()->cascadeOnDelete();
                $table->enum('abc_class', ['A', 'B', 'C']);
                $table->decimal('annual_consumption_value', 18, 3)->nullable();
                $table->decimal('percentage_of_total', 5, 2)->nullable();
                $table->decimal('cumulative_percentage', 5, 2)->nullable();
                $table->date('classification_date');
                $table->timestamps();
            });
        }

        // ==================== 7. تحسينات عامة ====================
        
        // جدول الإشعارات
        if (!Schema::hasTable('system_notifications')) {
            Schema::create('system_notifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('notifiable_type', 100)->nullable();
                $table->unsignedBigInteger('notifiable_id')->nullable();
                $table->string('title', 255);
                $table->text('message');
                $table->enum('type', ['info', 'warning', 'error', 'success'])->default('info');
                $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
                $table->string('action_url')->nullable();
                $table->boolean('is_read')->default(false);
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
                
                $table->index(['user_id', 'is_read']);
            });
        }
        
        // جدول سجل تغيير الحالات
        if (!Schema::hasTable('status_history')) {
            Schema::create('status_history', function (Blueprint $table) {
                $table->id();
                $table->string('model_type', 100);
                $table->unsignedBigInteger('model_id');
                $table->string('old_status', 50)->nullable();
                $table->string('new_status', 50);
                $table->text('reason')->nullable();
                $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                
                $table->index(['model_type', 'model_id']);
            });
        }
        
        // جدول المرفقات العام
        if (!Schema::hasTable('attachments')) {
            Schema::create('attachments', function (Blueprint $table) {
                $table->id();
                $table->string('attachable_type', 100);
                $table->unsignedBigInteger('attachable_id');
                $table->string('file_name', 255);
                $table->string('original_name', 255);
                $table->string('file_path', 500);
                $table->string('mime_type', 100)->nullable();
                $table->unsignedBigInteger('file_size')->nullable();
                $table->string('category', 100)->nullable();
                $table->text('description')->nullable();
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                
                $table->index(['attachable_type', 'attachable_id']);
            });
        }
        
        // جدول التعليقات العام
        if (!Schema::hasTable('comments')) {
            Schema::create('comments', function (Blueprint $table) {
                $table->id();
                $table->string('commentable_type', 100);
                $table->unsignedBigInteger('commentable_id');
                $table->foreignId('parent_id')->nullable()->constrained('comments')->cascadeOnDelete();
                $table->text('content');
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->boolean('is_internal')->default(false);
                $table->timestamps();
                $table->softDeletes();
                
                $table->index(['commentable_type', 'commentable_id']);
            });
        }

        // ==================== 8. تحسينات الموارد البشرية ====================
        
        // جدول الحضور التفصيلي
        if (!Schema::hasTable('attendance_punches')) {
            Schema::create('attendance_punches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
                $table->date('punch_date');
                $table->time('punch_time');
                $table->enum('punch_type', ['in', 'out', 'break_start', 'break_end']);
                $table->string('device_id', 100)->nullable();
                $table->decimal('latitude', 10, 8)->nullable();
                $table->decimal('longitude', 11, 8)->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                
                $table->index(['employee_id', 'punch_date']);
            });
        }
        
        // جدول سياسات الإجازات
        if (!Schema::hasTable('leave_policies')) {
            Schema::create('leave_policies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('leave_type_id')->constrained()->cascadeOnDelete();
                $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('job_title_id')->nullable()->constrained()->nullOnDelete();
                $table->decimal('annual_entitlement', 5, 1);
                $table->decimal('max_carry_forward', 5, 1)->nullable();
                $table->decimal('max_accumulation', 5, 1)->nullable();
                $table->integer('min_service_months')->default(0);
                $table->boolean('is_paid')->default(true);
                $table->boolean('requires_attachment')->default(false);
                $table->integer('max_consecutive_days')->nullable();
                $table->integer('advance_notice_days')->default(0);
                $table->text('conditions')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
        
        // جدول أرصدة الإجازات
        if (!Schema::hasTable('leave_balances')) {
            Schema::create('leave_balances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
                $table->foreignId('leave_type_id')->constrained()->cascadeOnDelete();
                $table->integer('year');
                $table->decimal('opening_balance', 5, 1)->default(0);
                $table->decimal('entitlement', 5, 1)->default(0);
                $table->decimal('used', 5, 1)->default(0);
                $table->decimal('pending', 5, 1)->default(0);
                $table->decimal('adjustment', 5, 1)->default(0);
                $table->decimal('carry_forward', 5, 1)->default(0);
                $table->decimal('closing_balance', 5, 1)->default(0);
                $table->timestamps();
                
                $table->unique(['employee_id', 'leave_type_id', 'year']);
            });
        }

        // ==================== 9. إضافة حقول للجداول الموجودة ====================
        
        // إضافة حقول Reorder Point للمواد
        if (Schema::hasTable('materials') && !Schema::hasColumn('materials', 'reorder_point')) {
            Schema::table('materials', function (Blueprint $table) {
                $table->decimal('reorder_point', 15, 3)->nullable();
                $table->decimal('safety_stock', 15, 3)->nullable();
                $table->decimal('economic_order_qty', 15, 3)->nullable();
                $table->integer('lead_time_days')->nullable();
                $table->decimal('maximum_stock', 15, 3)->nullable();
                $table->decimal('minimum_stock', 15, 3)->nullable();
            });
        }
    }

    public function down(): void
    {
        // Remove added columns
        if (Schema::hasColumn('materials', 'reorder_point')) {
            Schema::table('materials', function (Blueprint $table) {
                $table->dropColumn([
                    'reorder_point', 'safety_stock', 'economic_order_qty',
                    'lead_time_days', 'maximum_stock', 'minimum_stock'
                ]);
            });
        }

        // Drop tables
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('leave_policies');
        Schema::dropIfExists('attendance_punches');
        Schema::dropIfExists('comments');
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('status_history');
        Schema::dropIfExists('system_notifications');
        Schema::dropIfExists('material_abc_classifications');
        Schema::dropIfExists('location_inventory');
        Schema::dropIfExists('warehouse_locations');
        Schema::dropIfExists('bid_comparisons');
        Schema::dropIfExists('supplier_quote_items');
        Schema::dropIfExists('supplier_quotes');
        Schema::dropIfExists('rfq_suppliers');
        Schema::dropIfExists('rfq_items');
        Schema::dropIfExists('rfqs');
        Schema::dropIfExists('invoice_deduction_details');
        Schema::dropIfExists('invoice_materials_on_site');
        Schema::dropIfExists('wbs_constraints');
        Schema::dropIfExists('project_milestones');
        Schema::dropIfExists('invoice_price_adjustments');
        Schema::dropIfExists('price_adjustment_readings');
        Schema::dropIfExists('price_adjustment_indices');
        Schema::dropIfExists('tender_decision_criteria');
        Schema::dropIfExists('tender_risks');
        Schema::dropIfExists('tender_swot_analyses');
    }
};
