<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // جدول الموردين
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->string('supplier_type', 30);
            $table->string('tax_number', 50)->nullable();
            $table->string('commercial_register', 50)->nullable();
            
            // العنوان
            $table->foreignId('country_id')->nullable()->constrained();
            $table->foreignId('city_id')->nullable()->constrained();
            $table->text('address')->nullable();
            $table->string('postal_code', 20)->nullable();
            
            // الاتصال
            $table->string('phone', 50)->nullable();
            $table->string('mobile', 50)->nullable();
            $table->string('fax', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            
            // جهة الاتصال
            $table->string('contact_person')->nullable();
            $table->string('contact_phone', 50)->nullable();
            $table->string('contact_email')->nullable();
            
            // البنك
            $table->string('bank_name')->nullable();
            $table->string('bank_branch')->nullable();
            $table->string('bank_account', 50)->nullable();
            $table->string('iban', 50)->nullable();
            $table->string('swift_code', 20)->nullable();
            
            // الشروط
            $table->integer('payment_terms_days')->default(30);
            $table->decimal('credit_limit', 18, 3)->nullable();
            $table->decimal('balance', 18, 3)->default(0);
            $table->foreignId('currency_id')->nullable()->constrained('currencies');
            
            // التقييم
            $table->decimal('rating', 3, 2)->nullable();
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_blacklisted')->default(false);
            $table->text('blacklist_reason')->nullable();
            
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        // تخصصات المورد
        Schema::create('supplier_specializations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('specialization_id')->constrained();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        // تقييم الموردين
        Schema::create('supplier_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained();
            $table->foreignId('purchase_order_id')->nullable();
            
            $table->date('evaluation_date');
            $table->integer('quality_score')->default(0); // 1-10
            $table->integer('delivery_score')->default(0);
            $table->integer('price_score')->default(0);
            $table->integer('service_score')->default(0);
            $table->decimal('overall_score', 4, 2)->default(0);
            $table->text('comments')->nullable();
            
            $table->foreignId('evaluated_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_evaluations');
        Schema::dropIfExists('supplier_specializations');
        Schema::dropIfExists('suppliers');
    }
};
