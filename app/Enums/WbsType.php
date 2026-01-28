<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum WbsType: string implements HasLabel
{
    case PHASE = 'phase';
    case ACTIVITY = 'activity';
    case TASK = 'task';
    case MILESTONE = 'milestone';

    public function getLabel(): ?string
    {
        return match($this) {
            self::PHASE => 'مرحلة',
            self::ACTIVITY => 'نشاط',
            self::TASK => 'مهمة',
            self::MILESTONE => 'معلم',
        };
    }
}
