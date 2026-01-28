<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum VariationType: string implements HasLabel
{
    case QUANTITY = 'quantity';
    case ADDITIONAL = 'additional';
    case OMISSION = 'omission';
    case SPECIFICATION = 'specification';
    case DESIGN = 'design';
    case SITE = 'site';

    public function getLabel(): string
    {
        return match($this) {
            self::QUANTITY => 'تغيير كميات',
            self::ADDITIONAL => 'أعمال إضافية',
            self::OMISSION => 'حذف أعمال',
            self::SPECIFICATION => 'تغيير مواصفات',
            self::DESIGN => 'تغيير تصميم',
            self::SITE => 'تغيير موقع',
        };
    }

    public function isAddition(): bool
    {
        return in_array($this, [self::ADDITIONAL, self::QUANTITY]);
    }

    public function isDeduction(): bool
    {
        return $this === self::OMISSION;
    }
}
