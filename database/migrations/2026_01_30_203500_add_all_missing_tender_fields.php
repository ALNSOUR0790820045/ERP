<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            // تجزئة المناقصة (الحزم)
            if (!Schema::hasColumn('tenders', 'is_package_tender')) {
                $table->boolean('is_package_tender')->default(false)->after('tender_scope');
            }
            if (!Schema::hasColumn('tenders', 'package_count')) {
                $table->integer('package_count')->nullable()->after('is_package_tender');
            }
            if (!Schema::hasColumn('tenders', 'award_basis')) {
                $table->string('award_basis', 50)->nullable()->after('package_count');
            }

            // الموقع الإلكتروني للجهة المشترية
            if (!Schema::hasColumn('tenders', 'owner_website')) {
                $table->string('owner_website', 500)->nullable()->after('owner_email');
            }

            // مصدر التمويل
            if (!Schema::hasColumn('tenders', 'funding_source')) {
                $table->string('funding_source', 50)->nullable()->after('beneficiary_id');
            }
            if (!Schema::hasColumn('tenders', 'funder_name')) {
                $table->string('funder_name', 255)->nullable()->after('funding_source');
            }

            // الاستيضاحات
            if (!Schema::hasColumn('tenders', 'clarification_address')) {
                $table->string('clarification_address', 500)->nullable()->after('questions_deadline');
            }

            // التقديم الإلكتروني
            if (!Schema::hasColumn('tenders', 'electronic_submission')) {
                $table->string('electronic_submission', 50)->nullable()->after('submission_notes');
            }
            if (!Schema::hasColumn('tenders', 'submission_district')) {
                $table->string('submission_district', 255)->nullable()->after('submission_city');
            }
            if (!Schema::hasColumn('tenders', 'submission_box_number')) {
                $table->string('submission_box_number', 100)->nullable()->after('submission_building');
            }

            // تأمين الدخول
            if (!Schema::hasColumn('tenders', 'bid_bond_calculation')) {
                $table->string('bid_bond_calculation', 50)->nullable()->after('bid_bond_type');
            }
            if (!Schema::hasColumn('tenders', 'bid_bond_validity_days')) {
                $table->integer('bid_bond_validity_days')->nullable()->after('bid_bond_amount');
            }

            // ملاحظات إضافية
            if (!Schema::hasColumn('tenders', 'additional_notes')) {
                $table->text('additional_notes')->nullable()->after('other_requirements');
            }

            // التقييم الفني والمالي
            if (!Schema::hasColumn('tenders', 'technical_pass_score')) {
                $table->decimal('technical_pass_score', 5, 2)->nullable()->after('total_cost');
            }
            if (!Schema::hasColumn('tenders', 'technical_weight')) {
                $table->decimal('technical_weight', 5, 2)->nullable()->after('technical_pass_score');
            }
            if (!Schema::hasColumn('tenders', 'financial_weight')) {
                $table->decimal('financial_weight', 5, 2)->nullable()->after('technical_weight');
            }

            // التصحيحات الحسابية
            if (!Schema::hasColumn('tenders', 'allow_arithmetic_corrections')) {
                $table->boolean('allow_arithmetic_corrections')->default(true)->after('financial_weight');
            }
            if (!Schema::hasColumn('tenders', 'words_over_numbers_precedence')) {
                $table->boolean('words_over_numbers_precedence')->default(true)->after('allow_arithmetic_corrections');
            }

            // فترة الاعتراض
            if (!Schema::hasColumn('tenders', 'objection_period_start')) {
                $table->date('objection_period_start')->nullable()->after('objection_period_days');
            }
            if (!Schema::hasColumn('tenders', 'objection_period_end')) {
                $table->date('objection_period_end')->nullable()->after('objection_period_start');
            }
            if (!Schema::hasColumn('tenders', 'objection_fee')) {
                $table->decimal('objection_fee', 10, 2)->nullable()->after('objection_period_end');
            }

            // الأفضليات السعرية
            if (!Schema::hasColumn('tenders', 'allows_price_preferences')) {
                $table->boolean('allows_price_preferences')->default(false)->after('objection_fee');
            }
            if (!Schema::hasColumn('tenders', 'sme_preference_percentage')) {
                $table->decimal('sme_preference_percentage', 5, 2)->nullable()->after('allows_price_preferences');
            }
            if (!Schema::hasColumn('tenders', 'local_products_preference')) {
                $table->boolean('local_products_preference')->default(false)->after('sme_preference_percentage');
            }

            // التعاقد الفرعي
            if (!Schema::hasColumn('tenders', 'allows_subcontracting')) {
                $table->boolean('allows_subcontracting')->default(true)->after('local_products_preference');
            }
            if (!Schema::hasColumn('tenders', 'max_subcontracting_percentage')) {
                $table->decimal('max_subcontracting_percentage', 5, 2)->nullable()->after('allows_subcontracting');
            }
            if (!Schema::hasColumn('tenders', 'local_subcontractor_percentage')) {
                $table->decimal('local_subcontractor_percentage', 5, 2)->nullable()->after('max_subcontracting_percentage');
            }

            // الائتلافات
            if (!Schema::hasColumn('tenders', 'allows_consortium')) {
                $table->boolean('allows_consortium')->default(true)->after('local_subcontractor_percentage');
            }
            if (!Schema::hasColumn('tenders', 'max_consortium_members')) {
                $table->integer('max_consortium_members')->nullable()->after('allows_consortium');
            }

            // الإقرارات والالتزامات
            if (!Schema::hasColumn('tenders', 'esmp_required')) {
                $table->boolean('esmp_required')->default(false)->after('max_consortium_members');
            }
            if (!Schema::hasColumn('tenders', 'code_of_conduct_required')) {
                $table->boolean('code_of_conduct_required')->default(false)->after('esmp_required');
            }
            if (!Schema::hasColumn('tenders', 'anti_corruption_declaration_required')) {
                $table->boolean('anti_corruption_declaration_required')->default(false)->after('code_of_conduct_required');
            }
            if (!Schema::hasColumn('tenders', 'conflict_of_interest_declaration_required')) {
                $table->boolean('conflict_of_interest_declaration_required')->default(false)->after('anti_corruption_declaration_required');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $columns = [
                'is_package_tender',
                'package_count',
                'award_basis',
                'owner_website',
                'funding_source',
                'funder_name',
                'clarification_address',
                'electronic_submission',
                'submission_district',
                'submission_box_number',
                'bid_bond_calculation',
                'bid_bond_validity_days',
                'additional_notes',
                'technical_pass_score',
                'technical_weight',
                'financial_weight',
                'allow_arithmetic_corrections',
                'words_over_numbers_precedence',
                'objection_period_start',
                'objection_period_end',
                'objection_fee',
                'allows_price_preferences',
                'sme_preference_percentage',
                'local_products_preference',
                'allows_subcontracting',
                'max_subcontracting_percentage',
                'local_subcontractor_percentage',
                'allows_consortium',
                'max_consortium_members',
                'esmp_required',
                'code_of_conduct_required',
                'anti_corruption_declaration_required',
                'conflict_of_interest_declaration_required',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('tenders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
