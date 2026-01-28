<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ProjectStatus: string implements HasLabel, HasColor
{
    case PLANNING = 'planning';
    case MOBILIZATION = 'mobilization';
    case ACTIVE = 'active';
    case ON_HOLD = 'on_hold';
    case SUSPENDED = 'suspended';
    case COMPLETED = 'completed';
    case CLOSED = 'closed';
    case CANCELLED = 'cancelled';

    public function getLabel(): ?string
    {
        return match($this) {
            self::PLANNING => 'التخطيط',
            self::MOBILIZATION => 'التجهيز',
            self::ACTIVE => 'نشط',
            self::ON_HOLD => 'معلق',
            self::SUSPENDED => 'موقوف',
            self::COMPLETED => 'مكتمل',
            self::CLOSED => 'مغلق',
            self::CANCELLED => 'ملغي',
        };
    }

    public function getColor(): string|array|null
    {
        return match($this) {
            self::PLANNING => 'info',
            self::MOBILIZATION => 'warning',
            self::ACTIVE => 'success',
            self::ON_HOLD => 'gray',
            self::SUSPENDED => 'danger',
            self::COMPLETED => 'primary',
            self::CLOSED => 'gray',
            self::CANCELLED => 'danger',
        };
    }
}
