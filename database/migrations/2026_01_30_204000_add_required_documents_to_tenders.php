<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            // required_documents كـ JSON للـ Repeater
            if (!Schema::hasColumn('tenders', 'required_documents')) {
                $table->json('required_documents')->nullable()->after('other_requirements');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            if (Schema::hasColumn('tenders', 'required_documents')) {
                $table->dropColumn('required_documents');
            }
        });
    }
};
