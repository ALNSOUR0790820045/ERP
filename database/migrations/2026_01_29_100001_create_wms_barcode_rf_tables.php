<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // جداول الباركود
        Schema::create('wms_barcode_formats', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name_ar');
            $table->string('name_en');
            $table->enum('barcode_type', ['EAN13', 'EAN8', 'UPC-A', 'UPC-E', 'CODE128', 'CODE39', 'QR', 'DATAMATRIX', 'PDF417', 'GS1-128']);
            $table->enum('entity_type', ['material', 'location', 'pallet', 'container', 'shipment', 'receipt', 'transfer', 'employee', 'equipment']);
            $table->string('prefix', 20)->nullable();
            $table->string('suffix', 20)->nullable();
            $table->integer('length')->nullable();
            $table->boolean('include_check_digit')->default(true);
            $table->json('format_pattern')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('wms_barcodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('format_id')->constrained('wms_barcode_formats');
            $table->string('barcode_value', 100)->unique();
            $table->string('barcode_type', 20);
            $table->morphs('barcodeable'); // material_id, location_id, etc.
            $table->string('batch_number')->nullable();
            $table->string('serial_number')->nullable();
            $table->date('manufacture_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('quantity', 15, 3)->nullable();
            $table->string('unit')->nullable();
            $table->json('gs1_data')->nullable(); // GS1 Application Identifiers
            $table->enum('status', ['active', 'used', 'expired', 'cancelled'])->default('active');
            $table->text('image_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->index(['barcode_value', 'status']);
        });

        Schema::create('wms_barcode_prints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barcode_id')->constrained('wms_barcodes');
            $table->foreignId('template_id')->nullable()->constrained('wms_label_templates');
            $table->foreignId('printer_id')->nullable()->constrained('wms_printers');
            $table->integer('copies')->default(1);
            $table->enum('status', ['pending', 'printing', 'printed', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->foreignId('printed_by')->nullable()->constrained('users');
            $table->timestamp('printed_at')->nullable();
            $table->timestamps();
        });

        // قوالب الملصقات
        Schema::create('wms_label_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name_ar');
            $table->string('name_en');
            $table->enum('label_type', ['product', 'location', 'pallet', 'shipping', 'receipt', 'custom']);
            $table->decimal('width_mm', 8, 2);
            $table->decimal('height_mm', 8, 2);
            $table->enum('orientation', ['portrait', 'landscape'])->default('portrait');
            $table->json('layout')->nullable(); // Label layout definition
            $table->json('fields')->nullable(); // Fields to include
            $table->text('zpl_template')->nullable(); // ZPL code for Zebra printers
            $table->text('html_template')->nullable(); // HTML template for web printing
            $table->boolean('include_barcode')->default(true);
            $table->boolean('include_qr')->default(false);
            $table->string('barcode_position', 20)->default('bottom');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // الطابعات
        Schema::create('wms_printers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->enum('printer_type', ['zebra', 'datamax', 'honeywell', 'brother', 'tsc', 'sato', 'generic']);
            $table->string('model')->nullable();
            $table->enum('connection_type', ['network', 'usb', 'serial', 'bluetooth', 'cloud']);
            $table->string('ip_address')->nullable();
            $table->integer('port')->nullable();
            $table->string('mac_address')->nullable();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses');
            $table->foreignId('location_id')->nullable()->constrained('warehouse_locations');
            $table->json('settings')->nullable();
            $table->enum('status', ['online', 'offline', 'error', 'maintenance'])->default('offline');
            $table->timestamp('last_ping_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // أجهزة RF/Mobile
        Schema::create('wms_rf_devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_code', 50)->unique();
            $table->string('device_name');
            $table->enum('device_type', ['handheld', 'forklift', 'wearable', 'tablet', 'fixed']);
            $table->string('manufacturer')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('mac_address')->nullable();
            $table->string('ip_address')->nullable();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses');
            $table->foreignId('assigned_user_id')->nullable()->constrained('users');
            $table->enum('status', ['available', 'in_use', 'charging', 'maintenance', 'lost'])->default('available');
            $table->integer('battery_level')->nullable();
            $table->json('capabilities')->nullable(); // scan_1d, scan_2d, voice, etc.
            $table->json('settings')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->date('purchase_date')->nullable();
            $table->date('warranty_expiry')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // جلسات RF
        Schema::create('wms_rf_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('wms_rf_devices');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->timestamp('login_at');
            $table->timestamp('logout_at')->nullable();
            $table->enum('status', ['active', 'idle', 'ended', 'timeout'])->default('active');
            $table->string('app_version')->nullable();
            $table->json('permissions')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->integer('total_scans')->default(0);
            $table->integer('successful_scans')->default(0);
            $table->integer('failed_scans')->default(0);
            $table->timestamps();
            $table->index(['device_id', 'status']);
            $table->index(['user_id', 'status']);
        });

        // سجل عمليات المسح
        Schema::create('wms_scan_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->nullable()->constrained('wms_rf_sessions');
            $table->foreignId('device_id')->nullable()->constrained('wms_rf_devices');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->string('barcode_scanned');
            $table->enum('scan_type', ['product', 'location', 'pallet', 'container', 'document', 'employee', 'unknown']);
            $table->enum('operation', ['receive', 'putaway', 'pick', 'pack', 'ship', 'transfer', 'count', 'adjust', 'inquiry', 'verify']);
            $table->morphs('scannable'); // Reference to scanned entity
            $table->enum('result', ['success', 'not_found', 'invalid', 'duplicate', 'error'])->default('success');
            $table->text('error_message')->nullable();
            $table->foreignId('location_id')->nullable()->constrained('warehouse_locations');
            $table->decimal('quantity', 15, 3)->nullable();
            $table->json('scan_data')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->timestamp('scanned_at');
            $table->timestamps();
            $table->index(['barcode_scanned', 'scanned_at']);
            $table->index(['user_id', 'scanned_at']);
            $table->index(['operation', 'result']);
        });

        // قوائم المسح (للعمليات المجمعة)
        Schema::create('wms_scan_lists', function (Blueprint $table) {
            $table->id();
            $table->string('list_code', 50)->unique();
            $table->string('name');
            $table->enum('list_type', ['pick_list', 'receive_list', 'count_list', 'verification_list', 'custom']);
            $table->morphs('reference'); // PO, SO, Transfer, etc.
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->foreignId('device_id')->nullable()->constrained('wms_rf_devices');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->integer('total_items')->default(0);
            $table->integer('scanned_items')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('wms_scan_list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scan_list_id')->constrained('wms_scan_lists')->cascadeOnDelete();
            $table->integer('line_number');
            $table->foreignId('material_id')->constrained('materials');
            $table->string('expected_barcode')->nullable();
            $table->string('scanned_barcode')->nullable();
            $table->foreignId('from_location_id')->nullable()->constrained('warehouse_locations');
            $table->foreignId('to_location_id')->nullable()->constrained('warehouse_locations');
            $table->decimal('expected_quantity', 15, 3);
            $table->decimal('scanned_quantity', 15, 3)->default(0);
            $table->decimal('variance_quantity', 15, 3)->default(0);
            $table->string('batch_number')->nullable();
            $table->string('serial_number')->nullable();
            $table->enum('status', ['pending', 'partial', 'completed', 'skipped'])->default('pending');
            $table->timestamp('scanned_at')->nullable();
            $table->foreignId('scanned_by')->nullable()->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // إعدادات GS1
        Schema::create('wms_gs1_settings', function (Blueprint $table) {
            $table->id();
            $table->string('company_prefix', 20);
            $table->string('gln', 13)->nullable(); // Global Location Number
            $table->string('gtin_prefix', 14)->nullable();
            $table->string('sscc_extension', 1)->default('0');
            $table->integer('serial_reference_length')->default(9);
            $table->bigInteger('last_serial_number')->default(0);
            $table->json('ai_settings')->nullable(); // Application Identifier settings
            $table->boolean('use_gs1_128')->default(true);
            $table->boolean('use_gs1_datamatrix')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_gs1_settings');
        Schema::dropIfExists('wms_scan_list_items');
        Schema::dropIfExists('wms_scan_lists');
        Schema::dropIfExists('wms_scan_logs');
        Schema::dropIfExists('wms_rf_sessions');
        Schema::dropIfExists('wms_rf_devices');
        Schema::dropIfExists('wms_printers');
        Schema::dropIfExists('wms_label_templates');
        Schema::dropIfExists('wms_barcode_prints');
        Schema::dropIfExists('wms_barcodes');
        Schema::dropIfExists('wms_barcode_formats');
    }
};
