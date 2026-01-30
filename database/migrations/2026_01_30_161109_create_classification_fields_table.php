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
        Schema::create('classification_fields', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar'); // الاسم بالعربية
            $table->string('name_en')->nullable(); // الاسم بالإنجليزية
            $table->string('code')->nullable()->unique(); // الرمز
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
        Schema::dropIfExists('classification_fields');
    }
};
