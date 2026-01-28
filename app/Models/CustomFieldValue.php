<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomFieldValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'custom_field_definition_id',
        'entity_type',
        'entity_id',
        'value',
        'file_path',
    ];

    // العلاقات
    public function definition(): BelongsTo
    {
        return $this->belongsTo(CustomFieldDefinition::class, 'custom_field_definition_id');
    }

    public function entity()
    {
        return $this->morphTo();
    }

    /**
     * الحصول على القيمة المنسقة
     */
    public function getFormattedValueAttribute()
    {
        $definition = $this->definition;
        
        switch ($definition->field_type) {
            case 'select':
            case 'radio':
                $options = $definition->options ?? [];
                return $options[$this->value] ?? $this->value;
                
            case 'multiselect':
            case 'checkbox':
                $values = json_decode($this->value, true) ?? [];
                $options = $definition->options ?? [];
                return array_map(fn($v) => $options[$v] ?? $v, $values);
                
            case 'date':
                return $this->value ? \Carbon\Carbon::parse($this->value)->format('Y-m-d') : null;
                
            case 'datetime':
                return $this->value ? \Carbon\Carbon::parse($this->value)->format('Y-m-d H:i') : null;
                
            case 'file':
            case 'image':
                return $this->file_path;
                
            default:
                return $this->value;
        }
    }

    /**
     * حفظ قيم الحقول المخصصة لكيان
     */
    public static function saveForEntity($entity, array $values): void
    {
        $entityType = get_class($entity);
        $entityId = $entity->id;

        foreach ($values as $fieldId => $value) {
            self::updateOrCreate(
                [
                    'custom_field_definition_id' => $fieldId,
                    'entity_type' => $entityType,
                    'entity_id' => $entityId,
                ],
                [
                    'value' => is_array($value) ? json_encode($value) : $value,
                ]
            );
        }
    }

    /**
     * الحصول على قيم الحقول المخصصة لكيان
     */
    public static function getForEntity($entity): array
    {
        $entityType = get_class($entity);
        $entityId = $entity->id;

        return self::where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->with('definition')
            ->get()
            ->pluck('value', 'custom_field_definition_id')
            ->toArray();
    }
}
