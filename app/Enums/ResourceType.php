<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ResourceType: string implements HasLabel
{
    case LABOR = 'labor';
    case EQUIPMENT = 'equipment';
    case MATERIAL = 'material';
    case SUBCONTRACTOR = 'subcontractor';

    public function getLabel(): ?string
    {
        return match($this) {
            self::LABOR => 'عمالة',
            self::EQUIPMENT => 'معدات',
            self::MATERIAL => 'مواد',
            self::SUBCONTRACTOR => 'مقاول فرعي',
        };
    }
}
