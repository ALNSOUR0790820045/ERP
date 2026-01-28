<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // نتائج فتح المظاريف
        Schema::create('tender_opening_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->constrained()->cascadeOnDelete();
            $table->datetime('opening_date');
            $table->enum('opening_type', ['technical', 'financial'])->default('financial');
            $table->integer('our_rank')->nullable();
            $table->integer('total_bidders')->nullable();
            $table->decimal('our_price', 18, 3)->nullable();
            $table->decimal('lowest_price', 18, 3)->nullable();
            $table->decimal('highest_price', 18, 3)->nullable();
            $table->decimal('average_price', 18, 3)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // المنافسين في الفتح
        Schema::create('tender_competitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opening_result_id')->constrained('tender_opening_results')->cascadeOnDelete();
            $table->integer('rank');
            $table->string('company_name');
            $table->decimal('price', 18, 3)->nullable();
            $table->decimal('price_difference', 18, 3)->nullable();
            $table->decimal('price_difference_percentage', 5, 2)->nullable();
            $table->boolean('technically_qualified')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // سجل أنشطة العطاء
        Schema::create('tender_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->constrained()->cascadeOnDelete();
            $table->string('activity_type'); // status_change, document_upload, note, etc
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('old_value')->nullable();
            $table->string('new_value')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        // تحليل Go/No-Go
        Schema::create('tender_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->constrained()->cascadeOnDelete();
            
            // معايير التقييم
            $table->integer('specialization_score')->nullable(); // 1-5
            $table->integer('resources_score')->nullable();
            $table->integer('profitability_score')->nullable();
            $table->integer('competition_score')->nullable();
            $table->integer('relationship_score')->nullable();
            $table->integer('risk_score')->nullable();
            $table->integer('location_score')->nullable();
            $table->decimal('total_score', 5, 2)->nullable();
            
            // تحليل SWOT
            $table->text('strengths')->nullable();
            $table->text('weaknesses')->nullable();
            $table->text('opportunities')->nullable();
            $table->text('threats')->nullable();
            
            // تحليل المخاطر
            $table->text('risks_analysis')->nullable();
            
            // القرار
            $table->enum('decision', ['pending', 'go', 'no_go'])->default('pending');
            $table->text('decision_justification')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->datetime('decided_at')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tender_decisions');
        Schema::dropIfExists('tender_activities');
        Schema::dropIfExists('tender_competitors');
        Schema::dropIfExists('tender_opening_results');
    }
};
