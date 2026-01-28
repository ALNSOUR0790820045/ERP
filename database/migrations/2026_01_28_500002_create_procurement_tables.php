<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // طلبات الشراء
        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained();
            $table->foreignId('company_id')->nullable()->constrained();
            
            $table->string('request_number', 50)->unique();
            $table->date('request_date');
            $table->date('required_date')->nullable();
            $table->string('priority', 20)->default('normal');
            $table->text('purpose')->nullable();
            
            $table->string('status', 30)->default('draft');
            $table->foreignId('requested_by')->nullable()->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        // بنود طلب الشراء
        Schema::create('purchase_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('purchase_requests')->cascadeOnDelete();
            
            $table->string('item_code', 50)->nullable();
            $table->text('description');
            $table->decimal('quantity', 18, 4);
            $table->foreignId('unit_id')->nullable()->constrained('units');
            $table->decimal('estimated_price', 18, 3)->nullable();
            $table->text('specifications')->nullable();
            
            $table->timestamps();
        });

        // أوامر الشراء
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->nullable()->constrained('purchase_requests');
            $table->foreignId('supplier_id')->constrained();
            $table->foreignId('project_id')->nullable()->constrained();
            $table->foreignId('company_id')->nullable()->constrained();
            
            $table->string('po_number', 50)->unique();
            $table->date('po_date');
            $table->date('delivery_date')->nullable();
            $table->string('delivery_location')->nullable();
            
            $table->decimal('subtotal', 18, 3)->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('discount_amount', 18, 3)->default(0);
            $table->decimal('tax_percentage', 5, 2)->default(0);
            $table->decimal('tax_amount', 18, 3)->default(0);
            $table->decimal('total_amount', 18, 3)->default(0);
            $table->foreignId('currency_id')->nullable()->constrained('currencies');
            
            $table->integer('payment_terms_days')->default(30);
            $table->text('terms_conditions')->nullable();
            $table->text('notes')->nullable();
            
            $table->string('status', 30)->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        // بنود أمر الشراء
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('request_item_id')->nullable()->constrained('purchase_request_items');
            
            $table->string('item_code', 50)->nullable();
            $table->text('description');
            $table->decimal('quantity', 18, 4);
            $table->foreignId('unit_id')->nullable()->constrained('units');
            $table->decimal('unit_price', 18, 3)->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('total_price', 18, 3)->default(0);
            
            $table->decimal('received_qty', 18, 4)->default(0);
            $table->decimal('remaining_qty', 18, 4)->default(0);
            
            $table->timestamps();
        });

        // طلبات عروض الأسعار
        Schema::create('quotation_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->nullable()->constrained('purchase_requests');
            $table->foreignId('project_id')->nullable()->constrained();
            
            $table->string('rfq_number', 50)->unique();
            $table->date('rfq_date');
            $table->date('closing_date');
            $table->text('description')->nullable();
            
            $table->string('status', 30)->default('open');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        // عروض أسعار الموردين
        Schema::create('supplier_quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfq_id')->constrained('quotation_requests')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained();
            
            $table->string('quotation_number', 50)->nullable();
            $table->date('quotation_date');
            $table->date('validity_date')->nullable();
            $table->decimal('total_amount', 18, 3)->default(0);
            $table->foreignId('currency_id')->nullable()->constrained('currencies');
            
            $table->integer('delivery_days')->nullable();
            $table->integer('payment_terms_days')->nullable();
            $table->text('notes')->nullable();
            
            $table->boolean('is_selected')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_quotations');
        Schema::dropIfExists('quotation_requests');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('purchase_request_items');
        Schema::dropIfExists('purchase_requests');
    }
};
