<?php

namespace App\Enums;

enum OwnerType: string
{
    case GOVERNMENT = 'government';
    case PRIVATE = 'private';
    case INTERNATIONAL = 'international';

    public function label(): string
    {
        return match($this) {
            self::GOVERNMENT => 'حكومي',
            self::PRIVATE => 'خاص',
            self::INTERNATIONAL => 'دولي',
        };
    }
}
