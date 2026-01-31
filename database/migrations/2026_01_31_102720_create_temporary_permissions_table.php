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
        // الجدول موجود، نتأكد من الحقول الإضافية
        if (!Schema::hasTable('temporary_permissions')) {
            Schema::create('temporary_permissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('permissionable_type');
                $table->unsignedBigInteger('permissionable_id');
                $table->foreignId('stage_id')->nullable()->constrained('tender_stages')->nullOnDelete();
                $table->json('permissions');
                $table->string('reason')->nullable();
                $table->foreignId('granted_by')->constrained('users')->cascadeOnDelete();
                $table->timestamp('granted_at')->useCurrent();
                $table->timestamp('expires_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamp('revoked_at')->nullable();
                $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                
                $table->index(['user_id', 'is_active']);
                $table->index(['permissionable_type', 'permissionable_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temporary_permissions');
    }
};
