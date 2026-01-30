<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('classification_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar'); // الاسم بالعربية (مثل: أولى، ثانية، ثالثة)
            $table->string('name_en')->nullable(); // الاسم بالإنجليزية
            $table->string('code')->nullable()->unique(); // الرمز (مثل: A, B, C)
            $table->decimal('min_value', 15, 2)->nullable(); // الحد الأدنى للقيمة
            $table->decimal('max_value', 15, 2)->nullable(); // الحد الأعلى للقيمة
            $table->text('description')->nullable(); // الوصف
            $table->boolean('is_active')->default(true); // نشط
            $table->integer('sort_order')->default(0); // ترتيب العرض
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classification_categories');
    }
};
