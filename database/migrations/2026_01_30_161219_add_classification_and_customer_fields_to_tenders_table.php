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
        Schema::table('tenders', function (Blueprint $table) {
            // عنوان الجهة المشترية
            $table->string('buyer_country')->nullable();
            $table->string('buyer_city')->nullable();
            $table->string('buyer_area')->nullable();
            $table->string('buyer_street')->nullable();
            $table->string('buyer_building')->nullable();
            $table->string('buyer_po_box')->nullable();
            
            // الجهة المستفيدة
            $table->foreignId('beneficiary_id')->nullable()->constrained('customers')->nullOnDelete();
            
            // عنوان تقديم العروض
            $table->string('submission_country')->nullable();
            $table->string('submission_city')->nullable();
            $table->string('submission_area')->nullable();
            $table->string('submission_street')->nullable();
            $table->string('submission_building')->nullable();
            $table->text('submission_notes')->nullable();
            
            // حقول التصنيف
            $table->foreignId('classification_field_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('classification_specialty_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('classification_category_id')->nullable()->constrained()->nullOnDelete();
            
            // رابط Google Maps
            $table->string('google_maps_link')->nullable();
            
            // هل المناقصة بالإنجليزية
            $table->boolean('is_english_tender')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $table->dropColumn([
                'buyer_country',
                'buyer_city',
                'buyer_area',
                'buyer_street',
                'buyer_building',
                'buyer_po_box',
            ]);
            
            $table->dropForeign(['beneficiary_id']);
            $table->dropColumn('beneficiary_id');
            
            $table->dropColumn([
                'submission_country',
                'submission_city',
                'submission_area',
                'submission_street',
                'submission_building',
                'submission_notes',
            ]);
            
            $table->dropForeign(['classification_field_id']);
            $table->dropForeign(['classification_specialty_id']);
            $table->dropForeign(['classification_category_id']);
            $table->dropColumn([
                'classification_field_id',
                'classification_specialty_id',
                'classification_category_id',
            ]);
            
            $table->dropColumn('google_maps_link');
            $table->dropColumn('is_english_tender');
        });
    }
};
