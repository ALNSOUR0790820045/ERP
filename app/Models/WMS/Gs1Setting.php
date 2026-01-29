<?php

namespace App\Models\WMS;

use Illuminate\Database\Eloquent\Model;

class Gs1Setting extends Model
{
    protected $table = 'wms_gs1_settings';

    protected $fillable = [
        'company_prefix', 'gln', 'gtin_prefix', 'sscc_extension',
        'serial_reference_length', 'last_serial_number',
        'ai_settings', 'use_gs1_128', 'use_gs1_datamatrix',
    ];

    protected $casts = [
        'ai_settings' => 'array',
        'use_gs1_128' => 'boolean',
        'use_gs1_datamatrix' => 'boolean',
    ];

    // GS1 Application Identifiers
    const AI_GTIN = '01';           // Global Trade Item Number
    const AI_BATCH = '10';          // Batch/Lot Number
    const AI_PRODUCTION_DATE = '11'; // Production Date
    const AI_EXPIRY_DATE = '17';    // Expiration Date
    const AI_SERIAL = '21';         // Serial Number
    const AI_QUANTITY = '30';       // Quantity
    const AI_NET_WEIGHT = '3100';   // Net Weight (kg)
    const AI_SSCC = '00';           // Serial Shipping Container Code

    public static function getInstance(): self
    {
        return static::first() ?? static::create([
            'company_prefix' => '0000000',
            'serial_reference_length' => 9,
        ]);
    }

    public function generateGtin(string $itemReference): string
    {
        $gtin = $this->gtin_prefix . str_pad($itemReference, 14 - strlen($this->gtin_prefix) - 1, '0', STR_PAD_LEFT);
        return $gtin . $this->calculateCheckDigit($gtin);
    }

    public function generateSscc(): string
    {
        $this->increment('last_serial_number');
        
        $sscc = $this->sscc_extension 
            . $this->company_prefix 
            . str_pad($this->last_serial_number, $this->serial_reference_length, '0', STR_PAD_LEFT);
        
        return $sscc . $this->calculateCheckDigit($sscc);
    }

    public function generateGln(string $locationReference): string
    {
        $gln = $this->company_prefix . str_pad($locationReference, 13 - strlen($this->company_prefix) - 1, '0', STR_PAD_LEFT);
        return $gln . $this->calculateCheckDigit($gln);
    }

    public function generateGs1128(array $data): string
    {
        $barcode = '';
        
        if (isset($data['gtin'])) {
            $barcode .= '(01)' . $data['gtin'];
        }
        if (isset($data['batch'])) {
            $barcode .= '(10)' . $data['batch'];
        }
        if (isset($data['expiry_date'])) {
            $barcode .= '(17)' . $data['expiry_date']; // YYMMDD format
        }
        if (isset($data['serial'])) {
            $barcode .= '(21)' . $data['serial'];
        }
        if (isset($data['quantity'])) {
            $barcode .= '(30)' . $data['quantity'];
        }
        
        return $barcode;
    }

    public function parseGs1128(string $barcode): array
    {
        $result = [];
        $patterns = [
            '/\(01\)(\d{14})/' => 'gtin',
            '/\(10\)([^\(]+)/' => 'batch',
            '/\(17\)(\d{6})/' => 'expiry_date',
            '/\(21\)([^\(]+)/' => 'serial',
            '/\(30\)(\d+)/' => 'quantity',
            '/\(00\)(\d{18})/' => 'sscc',
        ];
        
        foreach ($patterns as $pattern => $key) {
            if (preg_match($pattern, $barcode, $matches)) {
                $result[$key] = $matches[1];
            }
        }
        
        return $result;
    }

    protected function calculateCheckDigit(string $number): string
    {
        $sum = 0;
        $length = strlen($number);
        for ($i = 0; $i < $length; $i++) {
            $digit = (int)$number[$length - 1 - $i];
            $sum += ($i % 2 === 0) ? $digit * 3 : $digit;
        }
        return (string)((10 - ($sum % 10)) % 10);
    }
}
