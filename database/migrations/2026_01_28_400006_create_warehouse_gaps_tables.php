<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // مخزون الأمان
        if (!Schema::hasTable('safety_stocks')) {
            Schema::create('safety_stocks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('material_id')->constrained()->cascadeOnDelete();
                $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
                $table->decimal('min_quantity', 18, 4);
                $table->decimal('max_quantity', 18, 4)->nullable();
                $table->decimal('safety_quantity', 18, 4);
                $table->decimal('lead_time_days', 5, 1)->default(0);
                $table->decimal('daily_usage', 18, 4)->nullable();
                $table->boolean('auto_reorder')->default(false);
                $table->timestamps();
                $table->unique(['material_id', 'warehouse_id']);
            });
        }

        // بطاقة الصنف
        if (!Schema::hasTable('bin_cards')) {
            Schema::create('bin_cards', function (Blueprint $table) {
                $table->id();
                $table->foreignId('material_id')->constrained()->cascadeOnDelete();
                $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
                $table->date('transaction_date');
                $table->enum('transaction_type', ['in', 'out', 'adjustment', 'transfer_in', 'transfer_out', 'return']);
                $table->string('reference_type')->nullable();
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->string('reference_number')->nullable();
                $table->decimal('quantity_in', 18, 4)->default(0);
                $table->decimal('quantity_out', 18, 4)->default(0);
                $table->decimal('balance', 18, 4)->default(0);
                $table->decimal('unit_cost', 18, 4)->nullable();
                $table->decimal('total_cost', 18, 3)->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['material_id', 'warehouse_id', 'transaction_date']);
            });
        }

        // حجز المواد
        if (!Schema::hasTable('material_reservations')) {
            Schema::create('material_reservations', function (Blueprint $table) {
                $table->id();
                $table->string('reservation_number')->unique();
                $table->foreignId('material_id')->constrained()->cascadeOnDelete();
                $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
                $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
                $table->decimal('quantity', 18, 4);
                $table->date('reservation_date');
                $table->date('required_date');
                $table->date('expiry_date')->nullable();
                $table->text('purpose')->nullable();
                $table->enum('status', ['pending', 'confirmed', 'fulfilled', 'cancelled', 'expired'])->default('pending');
                $table->foreignId('reserved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('fulfilled_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('fulfilled_at')->nullable();
                $table->timestamps();
            });
        }

        // إرجاع المواد
        if (!Schema::hasTable('material_returns')) {
            Schema::create('material_returns', function (Blueprint $table) {
                $table->id();
                $table->string('return_number')->unique();
                $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
                $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
                $table->enum('return_type', ['to_warehouse', 'to_supplier', 'from_project'])->default('to_warehouse');
                $table->date('return_date');
                $table->text('reason');
                $table->decimal('total_value', 18, 3)->default(0);
                $table->enum('status', ['draft', 'pending', 'approved', 'completed', 'rejected'])->default('draft');
                $table->foreignId('returned_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // بنود الإرجاع
        if (!Schema::hasTable('material_return_items')) {
            Schema::create('material_return_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('material_return_id')->constrained()->cascadeOnDelete();
                $table->foreignId('material_id')->constrained()->cascadeOnDelete();
                $table->decimal('quantity', 18, 4);
                $table->decimal('unit_cost', 18, 4)->nullable();
                $table->decimal('total_cost', 18, 3)->nullable();
                $table->enum('condition', ['good', 'damaged', 'expired'])->default('good');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // التخلص من الخردة
        if (!Schema::hasTable('scrap_disposals')) {
            Schema::create('scrap_disposals', function (Blueprint $table) {
                $table->id();
                $table->string('disposal_number')->unique();
                $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
                $table->date('disposal_date');
                $table->enum('disposal_method', ['sale', 'recycle', 'destroy', 'donate', 'other'])->default('sale');
                $table->decimal('total_value', 18, 3)->default(0);
                $table->decimal('recovery_value', 18, 3)->default(0);
                $table->string('buyer_name')->nullable();
                $table->text('notes')->nullable();
                $table->enum('status', ['draft', 'approved', 'completed'])->default('draft');
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // تتبع الصلاحية
        if (!Schema::hasTable('expiry_trackings')) {
            Schema::create('expiry_trackings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('material_id')->constrained()->cascadeOnDelete();
                $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
                $table->string('batch_number')->nullable();
                $table->decimal('quantity', 18, 4);
                $table->date('manufacture_date')->nullable();
                $table->date('expiry_date');
                $table->integer('alert_days_before')->default(30);
                $table->enum('status', ['active', 'expiring_soon', 'expired', 'disposed'])->default('active');
                $table->boolean('alert_sent')->default(false);
                $table->timestamps();
            });
        }

        // تتبع الدفعات
        if (!Schema::hasTable('batch_trackings')) {
            Schema::create('batch_trackings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('material_id')->constrained()->cascadeOnDelete();
                $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
                $table->string('batch_number');
                $table->date('production_date')->nullable();
                $table->date('expiry_date')->nullable();
                $table->string('supplier_batch')->nullable();
                $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('purchase_order_id')->nullable()->constrained()->nullOnDelete();
                $table->decimal('received_quantity', 18, 4);
                $table->decimal('current_quantity', 18, 4);
                $table->decimal('unit_cost', 18, 4)->nullable();
                $table->enum('status', ['active', 'depleted', 'expired', 'recalled'])->default('active');
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->unique(['material_id', 'warehouse_id', 'batch_number']);
            });
        }

        // الأرقام التسلسلية
        if (!Schema::hasTable('serial_numbers')) {
            Schema::create('serial_numbers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('material_id')->constrained()->cascadeOnDelete();
                $table->string('serial_number')->unique();
                $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
                $table->date('receipt_date')->nullable();
                $table->date('warranty_start')->nullable();
                $table->date('warranty_end')->nullable();
                $table->decimal('purchase_cost', 18, 4)->nullable();
                $table->enum('status', ['available', 'reserved', 'issued', 'sold', 'returned', 'defective', 'scrapped'])->default('available');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // تقييم المخزون
        if (!Schema::hasTable('inventory_valuations')) {
            Schema::create('inventory_valuations', function (Blueprint $table) {
                $table->id();
                $table->string('reference')->unique();
                $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
                $table->date('valuation_date');
                $table->enum('valuation_method', ['fifo', 'lifo', 'weighted_average', 'standard'])->default('weighted_average');
                $table->decimal('total_quantity', 18, 4)->default(0);
                $table->decimal('total_value', 18, 3)->default(0);
                $table->json('items_data')->nullable();
                $table->enum('status', ['draft', 'final'])->default('draft');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // طريقة التكلفة
        if (!Schema::hasTable('costing_methods')) {
            Schema::create('costing_methods', function (Blueprint $table) {
                $table->id();
                $table->foreignId('material_id')->constrained()->cascadeOnDelete();
                $table->enum('method', ['fifo', 'lifo', 'weighted_average', 'standard', 'specific'])->default('weighted_average');
                $table->decimal('standard_cost', 18, 4)->nullable();
                $table->decimal('last_purchase_cost', 18, 4)->nullable();
                $table->decimal('average_cost', 18, 4)->nullable();
                $table->date('last_cost_update')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('costing_methods');
        Schema::dropIfExists('inventory_valuations');
        Schema::dropIfExists('serial_numbers');
        Schema::dropIfExists('batch_trackings');
        Schema::dropIfExists('expiry_trackings');
        Schema::dropIfExists('scrap_disposals');
        Schema::dropIfExists('material_return_items');
        Schema::dropIfExists('material_returns');
        Schema::dropIfExists('material_reservations');
        Schema::dropIfExists('bin_cards');
        Schema::dropIfExists('safety_stocks');
    }
};
