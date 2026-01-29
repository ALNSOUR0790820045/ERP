<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * نظام الحقول المخصصة
     * Custom/Dynamic Fields System
     */
    public function up(): void
    {
        // جدول مجموعات الحقول - موجود مسبقاً
        if (!Schema::hasTable('custom_field_groups')) {
        Schema::create('custom_field_groups', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->text('description')->nullable();
            $table->string('entity_type'); // اسم الموديل (Contract, Employee, Project, etc.)
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_collapsible')->default(true);
            $table->boolean('is_collapsed_by_default')->default(false);
            $table->timestamps();
            
            $table->index(['entity_type', 'is_active']);
        });
        }

        // جدول تعريفات الحقول المخصصة - موجود مسبقاً
        if (!Schema::hasTable('custom_field_definitions')) {
        Schema::create('custom_field_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->nullable()->constrained('custom_field_groups')->nullOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->text('description')->nullable();
            $table->string('entity_type'); // اسم الموديل
            
            // نوع الحقل
            $table->enum('field_type', [
                'text', 'textarea', 'number', 'decimal', 'currency',
                'date', 'datetime', 'time',
                'select', 'multiselect', 'radio', 'checkbox',
                'file', 'image',
                'email', 'phone', 'url',
                'color', 'rating',
                'json', 'rich_text'
            ])->default('text');
            
            // خيارات الحقل
            $table->json('options')->nullable(); // للقوائم المنسدلة
            $table->string('default_value')->nullable();
            $table->string('placeholder')->nullable();
            $table->string('placeholder_ar')->nullable();
            
            // التحقق من الصحة
            $table->boolean('is_required')->default(false);
            $table->boolean('is_unique')->default(false);
            $table->integer('min_length')->nullable();
            $table->integer('max_length')->nullable();
            $table->decimal('min_value', 18, 4)->nullable();
            $table->decimal('max_value', 18, 4)->nullable();
            $table->string('regex_pattern')->nullable();
            $table->string('validation_message')->nullable();
            
            // خيارات العرض
            $table->integer('sort_order')->default(0);
            $table->string('width')->default('full'); // full, half, third, quarter
            $table->boolean('show_in_list')->default(false);
            $table->boolean('show_in_form')->default(true);
            $table->boolean('show_in_view')->default(true);
            $table->boolean('is_searchable')->default(false);
            $table->boolean('is_filterable')->default(false);
            $table->boolean('is_sortable')->default(false);
            
            // خيارات متقدمة
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false); // حقول النظام لا يمكن حذفها
            $table->json('visibility_conditions')->nullable(); // شروط الإظهار
            $table->json('metadata')->nullable();
            
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['entity_type', 'is_active', 'sort_order']);
        });
        }

        // جدول قيم الحقول المخصصة - موجود مسبقاً
        if (!Schema::hasTable('custom_field_values')) {
        Schema::create('custom_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('definition_id')->constrained('custom_field_definitions')->cascadeOnDelete();
            $table->morphs('entity'); // entity_type, entity_id
            
            // القيم المخزنة
            $table->text('value_text')->nullable();
            $table->decimal('value_number', 18, 4)->nullable();
            $table->date('value_date')->nullable();
            $table->datetime('value_datetime')->nullable();
            $table->boolean('value_boolean')->nullable();
            $table->json('value_json')->nullable();
            
            $table->timestamps();
            
            $table->unique(['definition_id', 'entity_type', 'entity_id'], 'custom_field_values_unique');
        });
        }

        // جدول القوالب (Templates)
        if (!Schema::hasTable('custom_field_templates')) {
        Schema::create('custom_field_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->text('description')->nullable();
            $table->string('entity_type');
            $table->json('field_ids'); // قائمة الحقول المشمولة
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_field_templates');
        Schema::dropIfExists('custom_field_values');
        Schema::dropIfExists('custom_field_definitions');
        Schema::dropIfExists('custom_field_groups');
    }
};
