<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // جدول الدول
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique();
            $table->string('name_ar', 100);
            $table->string('name_en', 100);
            $table->string('phone_code', 10)->nullable();
            $table->string('currency_code', 3)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // جدول المدن
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20)->nullable();
            $table->string('name_ar', 100);
            $table->string('name_en', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // جدول الشركات
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            
            // Basic Info
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->string('legal_name');
            
            // Registration
            $table->string('registration_number', 100);
            $table->string('tax_number', 100)->nullable();
            $table->string('vat_number', 100)->nullable();
            $table->string('social_security_number', 100)->nullable();
            $table->string('classification_number', 100)->nullable();
            $table->tinyInteger('classification_grade')->nullable();
            $table->date('establishment_date')->nullable();
            
            // Contact
            $table->text('address')->nullable();
            $table->foreignId('city_id')->nullable()->constrained();
            $table->foreignId('country_id')->nullable()->constrained();
            $table->string('postal_code', 20)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('fax', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            
            // Settings
            $table->string('logo', 500)->nullable();
            $table->foreignId('default_currency_id')->nullable()->constrained('currencies');
            $table->tinyInteger('fiscal_year_start')->default(1);
            
            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        // جدول الفروع
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            
            $table->string('code', 20);
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->enum('branch_type', ['main', 'sub', 'office', 'site'])->default('sub');
            $table->foreignId('parent_branch_id')->nullable()->constrained('branches');
            $table->foreignId('manager_id')->nullable()->constrained('users');
            
            $table->text('address')->nullable();
            $table->foreignId('city_id')->nullable()->constrained();
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            
            $table->boolean('is_active')->default(true);
            
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            
            $table->unique(['company_id', 'code']);
        });

        // جدول السنوات المالية
        Schema::create('fiscal_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            
            $table->string('name', 100);
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->boolean('is_current')->default(false);
            
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        // جدول الفترات المحاسبية
        Schema::create('fiscal_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiscal_year_id')->constrained()->cascadeOnDelete();
            
            $table->tinyInteger('period_number');
            $table->string('name', 100);
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['open', 'closed'])->default('open');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscal_periods');
        Schema::dropIfExists('fiscal_years');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('companies');
        Schema::dropIfExists('cities');
        Schema::dropIfExists('countries');
    }
};
