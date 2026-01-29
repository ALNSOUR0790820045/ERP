<?php

namespace App\Models\WMS;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BarcodeFormat extends Model
{
    protected $table = 'wms_barcode_formats';

    protected $fillable = [
        'code', 'name_ar', 'name_en', 'barcode_type', 'entity_type',
        'prefix', 'suffix', 'length', 'include_check_digit',
        'format_pattern', 'description', 'is_active',
    ];

    protected $casts = [
        'include_check_digit' => 'boolean',
        'is_active' => 'boolean',
        'format_pattern' => 'array',
    ];

    const TYPE_EAN13 = 'EAN13';
    const TYPE_EAN8 = 'EAN8';
    const TYPE_CODE128 = 'CODE128';
    const TYPE_CODE39 = 'CODE39';
    const TYPE_QR = 'QR';
    const TYPE_DATAMATRIX = 'DATAMATRIX';
    const TYPE_GS1_128 = 'GS1-128';

    public function barcodes(): HasMany
    {
        return $this->hasMany(Barcode::class, 'format_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForEntity($query, string $entityType)
    {
        return $query->where('entity_type', $entityType);
    }

    public function generateBarcode(string $data): string
    {
        $barcode = ($this->prefix ?? '') . $data . ($this->suffix ?? '');
        
        if ($this->include_check_digit) {
            $barcode .= $this->calculateCheckDigit($barcode);
        }
        
        return $barcode;
    }

    public function calculateCheckDigit(string $barcode): string
    {
        // Modulo 10 check digit (used by EAN, UPC, etc.)
        $sum = 0;
        $length = strlen($barcode);
        for ($i = 0; $i < $length; $i++) {
            $digit = (int)$barcode[$length - 1 - $i];
            $sum += ($i % 2 === 0) ? $digit * 3 : $digit;
        }
        return (string)((10 - ($sum % 10)) % 10);
    }
}
