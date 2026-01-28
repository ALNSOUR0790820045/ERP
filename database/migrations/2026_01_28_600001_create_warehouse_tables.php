<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // المستودعات
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained();
            $table->foreignId('branch_id')->nullable()->constrained();
            $table->foreignId('project_id')->nullable()->constrained();
            
            $table->string('code', 20)->unique();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->string('warehouse_type', 30)->default('general');
            $table->text('address')->nullable();
            $table->string('manager_name')->nullable();
            $table->string('phone', 50)->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // فئات المواد
        Schema::create('material_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('material_categories');
            $table->string('code', 20)->unique();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->integer('level')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // المواد
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('material_categories');
            
            $table->string('code', 50)->unique();
            $table->string('barcode', 50)->nullable();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->text('description')->nullable();
            $table->text('specifications')->nullable();
            
            $table->foreignId('unit_id')->nullable()->constrained('units');
            $table->foreignId('purchase_unit_id')->nullable()->constrained('units');
            $table->decimal('conversion_factor', 10, 4)->default(1);
            
            $table->decimal('min_stock', 18, 4)->default(0);
            $table->decimal('max_stock', 18, 4)->nullable();
            $table->decimal('reorder_point', 18, 4)->nullable();
            $table->decimal('reorder_qty', 18, 4)->nullable();
            
            $table->decimal('last_purchase_price', 18, 3)->nullable();
            $table->decimal('average_cost', 18, 3)->nullable();
            $table->decimal('standard_cost', 18, 3)->nullable();
            
            $table->string('valuation_method', 20)->default('average'); // average, fifo, lifo
            $table->boolean('is_serialized')->default(false);
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            $table->softDeletes();
        });

        // أرصدة المخزون
        Schema::create('inventory_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('material_id')->constrained()->cascadeOnDelete();
            
            $table->decimal('quantity', 18, 4)->default(0);
            $table->decimal('reserved_qty', 18, 4)->default(0);
            $table->decimal('available_qty', 18, 4)->default(0);
            $table->decimal('average_cost', 18, 3)->default(0);
            $table->decimal('total_value', 18, 3)->default(0);
            
            $table->string('location', 50)->nullable();
            $table->date('last_count_date')->nullable();
            
            $table->timestamps();
            $table->unique(['warehouse_id', 'material_id']);
        });

        // حركات المخزون
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained();
            $table->foreignId('material_id')->constrained();
            
            $table->string('transaction_type', 30);
            $table->string('transaction_number', 50);
            $table->date('transaction_date');
            
            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            
            $table->decimal('quantity', 18, 4);
            $table->decimal('unit_cost', 18, 3)->default(0);
            $table->decimal('total_cost', 18, 3)->default(0);
            
            $table->decimal('balance_before', 18, 4)->default(0);
            $table->decimal('balance_after', 18, 4)->default(0);
            
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            
            $table->index(['warehouse_id', 'material_id', 'transaction_date']);
        });

        // أذونات الاستلام
        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained();
            $table->foreignId('purchase_order_id')->nullable()->constrained();
            $table->foreignId('supplier_id')->nullable()->constrained();
            $table->foreignId('project_id')->nullable()->constrained();
            
            $table->string('receipt_number', 50)->unique();
            $table->date('receipt_date');
            $table->string('delivery_note', 100)->nullable();
            $table->string('invoice_number', 100)->nullable();
            
            $table->string('status', 30)->default('draft');
            $table->text('notes')->nullable();
            
            $table->foreignId('received_by')->nullable()->constrained('users');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        // بنود إذن الاستلام
        Schema::create('goods_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receipt_id')->constrained('goods_receipts')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained();
            $table->foreignId('po_item_id')->nullable()->constrained('purchase_order_items');
            
            $table->decimal('ordered_qty', 18, 4)->default(0);
            $table->decimal('received_qty', 18, 4);
            $table->decimal('accepted_qty', 18, 4)->default(0);
            $table->decimal('rejected_qty', 18, 4)->default(0);
            $table->decimal('unit_cost', 18, 3)->default(0);
            
            $table->string('rejection_reason')->nullable();
            $table->string('location', 50)->nullable();
            
            $table->timestamps();
        });

        // أذونات الصرف
        Schema::create('goods_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained();
            $table->foreignId('project_id')->nullable()->constrained();
            
            $table->string('issue_number', 50)->unique();
            $table->date('issue_date');
            $table->string('issue_type', 30)->default('project');
            $table->text('purpose')->nullable();
            
            $table->string('status', 30)->default('draft');
            $table->foreignId('requested_by')->nullable()->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->foreignId('issued_by')->nullable()->constrained('users');
            
            $table->timestamps();
        });

        // بنود إذن الصرف
        Schema::create('goods_issue_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_id')->constrained('goods_issues')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained();
            
            $table->decimal('requested_qty', 18, 4);
            $table->decimal('issued_qty', 18, 4)->default(0);
            $table->decimal('unit_cost', 18, 3)->default(0);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_issue_items');
        Schema::dropIfExists('goods_issues');
        Schema::dropIfExists('goods_receipt_items');
        Schema::dropIfExists('goods_receipts');
        Schema::dropIfExists('inventory_transactions');
        Schema::dropIfExists('inventory_balances');
        Schema::dropIfExists('materials');
        Schema::dropIfExists('material_categories');
        Schema::dropIfExists('warehouses');
    }
};
