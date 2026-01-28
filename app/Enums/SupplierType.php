<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SupplierType: string implements HasLabel, HasColor
{
    case MATERIAL = 'material';
    case SERVICE = 'service';
    case SUBCONTRACTOR = 'subcontractor';
    case EQUIPMENT = 'equipment';
    case MIXED = 'mixed';

    public function getLabel(): ?string
    {
        return match($this) {
            self::MATERIAL => 'مواد',
            self::SERVICE => 'خدمات',
            self::SUBCONTRACTOR => 'مقاول فرعي',
            self::EQUIPMENT => 'معدات',
            self::MIXED => 'متعدد',
        };
    }

    public function getColor(): string|array|null
    {
        return match($this) {
            self::MATERIAL => 'info',
            self::SERVICE => 'warning',
            self::SUBCONTRACTOR => 'success',
            self::EQUIPMENT => 'primary',
            self::MIXED => 'gray',
        };
    }
}
