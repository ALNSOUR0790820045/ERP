<?php

namespace App\Models\WMS;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabelTemplate extends Model
{
    protected $table = 'wms_label_templates';

    protected $fillable = [
        'code', 'name_ar', 'name_en', 'label_type', 'width_mm', 'height_mm',
        'orientation', 'layout', 'fields', 'zpl_template', 'html_template',
        'include_barcode', 'include_qr', 'barcode_position', 'is_default', 'is_active',
    ];

    protected $casts = [
        'width_mm' => 'decimal:2',
        'height_mm' => 'decimal:2',
        'layout' => 'array',
        'fields' => 'array',
        'include_barcode' => 'boolean',
        'include_qr' => 'boolean',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    const TYPE_PRODUCT = 'product';
    const TYPE_LOCATION = 'location';
    const TYPE_PALLET = 'pallet';
    const TYPE_SHIPPING = 'shipping';
    const TYPE_RECEIPT = 'receipt';
    const TYPE_CUSTOM = 'custom';

    public function prints(): HasMany
    {
        return $this->hasMany(BarcodePrint::class, 'template_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeForType($query, string $type)
    {
        return $query->where('label_type', $type);
    }

    public function renderZpl(array $data): string
    {
        $zpl = $this->zpl_template ?? '';
        foreach ($data as $key => $value) {
            $zpl = str_replace('{{' . $key . '}}', $value, $zpl);
        }
        return $zpl;
    }

    public function renderHtml(array $data): string
    {
        $html = $this->html_template ?? '';
        foreach ($data as $key => $value) {
            $html = str_replace('{{' . $key . '}}', $value, $html);
        }
        return $html;
    }

    public function setAsDefault(): void
    {
        static::where('label_type', $this->label_type)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);
        
        $this->update(['is_default' => true]);
    }
}
