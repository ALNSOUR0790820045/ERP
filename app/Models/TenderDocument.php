<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenderDocument extends Model
{
    protected $fillable = [
        'tender_id',
        'document_type',
        'file_name',
        'original_name',
        'file_path',
        'file_type',
        'file_size',
        'version',
        'notes',
        'uploaded_by',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'version' => 'integer',
    ];

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getDocumentTypeLabel(): string
    {
        return match($this->document_type) {
            'specifications' => 'كراسة الشروط والمواصفات',
            'boq' => 'جدول الكميات',
            'drawings' => 'المخططات والرسومات',
            'instructions' => 'تعليمات مقدمي العطاءات',
            'general_conditions' => 'الشروط العامة',
            'special_conditions' => 'الشروط الخاصة',
            'forms' => 'نماذج الضمانات',
            'addendum' => 'ملحقات وتعديلات',
            'site_visit' => 'محضر زيارة الموقع',
            'questions_answers' => 'أسئلة وأجوبة',
            'other' => 'أخرى',
            default => $this->document_type,
        };
    }

    public function getFileSizeForHumans(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
