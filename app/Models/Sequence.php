<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sequence extends Model
{
    protected $fillable = [
        'company_id',
        'code',
        'name',
        'document_type',
        'prefix',
        'suffix',
        'next_number',
        'min_digits',
        'reset_period',
        'include_year',
        'include_branch',
        'current_year',
    ];

    protected $casts = [
        'next_number' => 'integer',
        'min_digits' => 'integer',
        'include_year' => 'boolean',
        'include_branch' => 'boolean',
        'current_year' => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function getNextNumber(?string $branchCode = null): string
    {
        $this->checkReset();
        
        $parts = [];
        
        if ($this->prefix) {
            $parts[] = $this->prefix;
        }
        
        if ($this->include_branch && $branchCode) {
            $parts[] = $branchCode;
        }
        
        if ($this->include_year) {
            $parts[] = date('Y');
        }
        
        $parts[] = str_pad($this->next_number, $this->min_digits, '0', STR_PAD_LEFT);
        
        if ($this->suffix) {
            $parts[] = $this->suffix;
        }
        
        $this->increment('next_number');
        
        return implode('-', $parts);
    }

    protected function checkReset(): void
    {
        $currentYear = (int) date('Y');
        $currentMonth = (int) date('m');
        
        $shouldReset = false;
        
        if ($this->reset_period === 'yearly' && $this->current_year !== $currentYear) {
            $shouldReset = true;
        }
        
        if ($shouldReset) {
            $this->update([
                'next_number' => 1,
                'current_year' => $currentYear,
            ]);
        }
    }

    public function preview(?string $branchCode = null): string
    {
        $parts = [];
        
        if ($this->prefix) {
            $parts[] = $this->prefix;
        }
        
        if ($this->include_branch && $branchCode) {
            $parts[] = $branchCode;
        }
        
        if ($this->include_year) {
            $parts[] = date('Y');
        }
        
        $parts[] = str_pad($this->next_number, $this->min_digits, '0', STR_PAD_LEFT);
        
        if ($this->suffix) {
            $parts[] = $this->suffix;
        }
        
        return implode('-', $parts);
    }

    public static function getDocumentTypes(): array
    {
        return [
            'tender' => 'عطاء',
            'contract' => 'عقد',
            'project' => 'مشروع',
            'invoice' => 'فاتورة',
            'payment_certificate' => 'شهادة دفع',
            'purchase_order' => 'أمر شراء',
            'purchase_request' => 'طلب شراء',
            'goods_receipt' => 'استلام بضاعة',
            'goods_issue' => 'صرف بضاعة',
            'journal_entry' => 'قيد يومية',
            'employee' => 'موظف',
        ];
    }
}
