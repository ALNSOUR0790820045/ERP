<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum WeatherCondition: string implements HasLabel
{
    case SUNNY = 'sunny';
    case CLOUDY = 'cloudy';
    case RAINY = 'rainy';
    case STORMY = 'stormy';
    case DUSTY = 'dusty';
    case HOT = 'hot';
    case COLD = 'cold';

    public function getLabel(): ?string
    {
        return match($this) {
            self::SUNNY => 'مشمس',
            self::CLOUDY => 'غائم',
            self::RAINY => 'ماطر',
            self::STORMY => 'عاصف',
            self::DUSTY => 'مغبر',
            self::HOT => 'حار جداً',
            self::COLD => 'بارد جداً',
        };
    }
}
