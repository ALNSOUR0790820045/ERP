<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('workflow_steps') && !Schema::hasColumn('workflow_steps', 'step_type')) {
            Schema::table('workflow_steps', function (Blueprint $table) {
                $table->string('step_type')->default('action')->after('description');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('workflow_steps', 'step_type')) {
            Schema::table('workflow_steps', function (Blueprint $table) {
                $table->dropColumn('step_type');
            });
        }
    }
};
