<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ProjectPriority: string implements HasLabel, HasColor
{
    case CRITICAL = 'critical';
    case HIGH = 'high';
    case MEDIUM = 'medium';
    case LOW = 'low';

    public function getLabel(): ?string
    {
        return match($this) {
            self::CRITICAL => 'حرج',
            self::HIGH => 'عالي',
            self::MEDIUM => 'متوسط',
            self::LOW => 'منخفض',
        };
    }

    public function getColor(): string|array|null
    {
        return match($this) {
            self::CRITICAL => 'danger',
            self::HIGH => 'warning',
            self::MEDIUM => 'info',
            self::LOW => 'gray',
        };
    }
}
