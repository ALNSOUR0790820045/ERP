<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InvoiceStatus: string implements HasLabel, HasColor
{
    case DRAFT = 'draft';
    case REVIEWED = 'reviewed';
    case SUBMITTED = 'submitted';
    case UNDER_REVIEW = 'under_review';
    case APPROVED = 'approved';
    case CERTIFIED = 'certified';
    case COLLECTION = 'collection';
    case PAID = 'paid';
    case REJECTED = 'rejected';

    public function getLabel(): ?string
    {
        return match($this) {
            self::DRAFT => 'مسودة',
            self::REVIEWED => 'تم المراجعة',
            self::SUBMITTED => 'مقدم',
            self::UNDER_REVIEW => 'قيد المراجعة',
            self::APPROVED => 'معتمد',
            self::CERTIFIED => 'مصدق',
            self::COLLECTION => 'قيد التحصيل',
            self::PAID => 'مدفوع',
            self::REJECTED => 'مرفوض',
        };
    }

    public function getColor(): string|array|null
    {
        return match($this) {
            self::DRAFT => 'gray',
            self::REVIEWED => 'info',
            self::SUBMITTED => 'warning',
            self::UNDER_REVIEW => 'warning',
            self::APPROVED => 'success',
            self::CERTIFIED => 'success',
            self::COLLECTION => 'info',
            self::PAID => 'primary',
            self::REJECTED => 'danger',
        };
    }
}
