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
            // حقول شراء العطاء
            $table->boolean('documents_purchased')->default(false)->after('documents_price');
            $table->date('purchase_date')->nullable()->after('documents_purchased');
            $table->string('purchase_receipt_number', 50)->nullable()->after('purchase_date');
            $table->boolean('site_visit_mandatory')->default(false)->after('site_visit_date');
            $table->boolean('site_visit_attended')->default(false)->after('site_visit_mandatory');
            $table->text('site_visit_notes')->nullable()->after('site_visit_attended');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $table->dropColumn([
                'documents_purchased',
                'purchase_date',
                'purchase_receipt_number',
                'site_visit_mandatory',
                'site_visit_attended',
                'site_visit_notes',
            ]);
        });
    }
};
