<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum VariationStatus: string implements HasLabel, HasColor
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case UNDER_REVIEW = 'under_review';
    case NEGOTIATING = 'negotiating';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';

    public function getLabel(): string
    {
        return match($this) {
            self::DRAFT => 'مسودة',
            self::SUBMITTED => 'مقدم',
            self::UNDER_REVIEW => 'قيد المراجعة',
            self::NEGOTIATING => 'تفاوض',
            self::APPROVED => 'معتمد',
            self::REJECTED => 'مرفوض',
            self::CANCELLED => 'ملغي',
        };
    }

    public function getColor(): string|array|null
    {
        return match($this) {
            self::DRAFT => 'gray',
            self::SUBMITTED => 'info',
            self::UNDER_REVIEW => 'warning',
            self::NEGOTIATING => 'warning',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
            self::CANCELLED => 'gray',
        };
    }
}
