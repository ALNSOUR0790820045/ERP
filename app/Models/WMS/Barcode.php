<?php

namespace App\Models\WMS;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Barcode extends Model
{
    protected $table = 'wms_barcodes';

    protected $fillable = [
        'format_id', 'barcode_value', 'barcode_type', 'barcodeable_type', 'barcodeable_id',
        'batch_number', 'serial_number', 'manufacture_date', 'expiry_date',
        'quantity', 'unit', 'gs1_data', 'status', 'image_path', 'created_by',
    ];

    protected $casts = [
        'manufacture_date' => 'date',
        'expiry_date' => 'date',
        'quantity' => 'decimal:3',
        'gs1_data' => 'array',
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_USED = 'used';
    const STATUS_EXPIRED = 'expired';
    const STATUS_CANCELLED = 'cancelled';

    public function format(): BelongsTo
    {
        return $this->belongsTo(BarcodeFormat::class, 'format_id');
    }

    public function barcodeable(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function prints(): HasMany
    {
        return $this->hasMany(BarcodePrint::class, 'barcode_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeByValue($query, string $value)
    {
        return $query->where('barcode_value', $value);
    }

    public static function findByValue(string $value): ?self
    {
        return static::where('barcode_value', $value)->first();
    }

    public function markAsUsed(): void
    {
        $this->update(['status' => self::STATUS_USED]);
    }

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function parseGS1(): array
    {
        // Parse GS1-128 Application Identifiers
        $data = [];
        if ($this->gs1_data) {
            return $this->gs1_data;
        }
        // Parse from barcode value if GS1-128
        return $data;
    }

    public static function generate(BarcodeFormat $format, Model $entity, array $data = []): self
    {
        $barcodeValue = $format->generateBarcode($data['value'] ?? $entity->getKey());
        
        return static::create([
            'format_id' => $format->id,
            'barcode_value' => $barcodeValue,
            'barcode_type' => $format->barcode_type,
            'barcodeable_type' => get_class($entity),
            'barcodeable_id' => $entity->getKey(),
            'batch_number' => $data['batch_number'] ?? null,
            'serial_number' => $data['serial_number'] ?? null,
            'quantity' => $data['quantity'] ?? null,
            'unit' => $data['unit'] ?? null,
        ]);
    }
}
