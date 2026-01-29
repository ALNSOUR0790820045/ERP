<?php

namespace App\Models\WMS;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BarcodePrint extends Model
{
    protected $table = 'wms_barcode_prints';

    protected $fillable = [
        'barcode_id', 'template_id', 'printer_id', 'copies',
        'status', 'error_message', 'printed_by', 'printed_at',
    ];

    protected $casts = [
        'printed_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_PRINTING = 'printing';
    const STATUS_PRINTED = 'printed';
    const STATUS_FAILED = 'failed';

    public function barcode(): BelongsTo
    {
        return $this->belongsTo(Barcode::class, 'barcode_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(LabelTemplate::class, 'template_id');
    }

    public function printer(): BelongsTo
    {
        return $this->belongsTo(Printer::class, 'printer_id');
    }

    public function printedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'printed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function print(): bool
    {
        if (!$this->printer || !$this->template) {
            $this->fail('Missing printer or template');
            return false;
        }

        $this->update(['status' => self::STATUS_PRINTING]);

        try {
            $data = $this->preparePrintData();
            $success = $this->printer->printLabel($this->template, $data, $this->copies);
            
            if ($success) {
                $this->markPrinted();
                return true;
            } else {
                $this->fail('Printer communication error');
                return false;
            }
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
            return false;
        }
    }

    public function markPrinted(): void
    {
        $this->update([
            'status' => self::STATUS_PRINTED,
            'printed_at' => now(),
        ]);
    }

    public function fail(string $error): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $error,
        ]);
    }

    protected function preparePrintData(): array
    {
        $barcode = $this->barcode;
        $entity = $barcode->barcodeable;
        
        return [
            'barcode' => $barcode->barcode_value,
            'barcode_type' => $barcode->barcode_type,
            'batch_number' => $barcode->batch_number,
            'serial_number' => $barcode->serial_number,
            'expiry_date' => $barcode->expiry_date?->format('Y-m-d'),
            'quantity' => $barcode->quantity,
            'unit' => $barcode->unit,
            'entity_name' => $entity?->name_ar ?? $entity?->name ?? '',
            'entity_code' => $entity?->code ?? '',
        ];
    }
}
