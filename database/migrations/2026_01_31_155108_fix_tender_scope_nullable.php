<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // تحديث القيم الفارغة لـ tender_scope
        DB::table('tenders')
            ->whereNull('tender_scope')
            ->update(['tender_scope' => 'local']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // لا يوجد تراجع
    }
};
