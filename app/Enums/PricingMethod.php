<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PricingMethod: string implements HasLabel
{
    case LUMP_SUM = 'lump_sum';
    case UNIT_RATE = 'unit_rate';
    case COST_PLUS = 'cost_plus';
    case GMP = 'gmp';
    case TARGET_COST = 'target_cost';

    public function getLabel(): string
    {
        return match($this) {
            self::LUMP_SUM => 'مبلغ مقطوع',
            self::UNIT_RATE => 'أسعار وحدات',
            self::COST_PLUS => 'التكلفة + نسبة',
            self::GMP => 'سقف مضمون',
            self::TARGET_COST => 'تكلفة مستهدفة',
        };
    }

    public function getRiskLevel(): string
    {
        return match($this) {
            self::LUMP_SUM => 'مخاطرة على المقاول',
            self::UNIT_RATE => 'مخاطرة مشتركة',
            self::COST_PLUS => 'مخاطرة على المالك',
            self::GMP => 'مخاطرة مشتركة',
            self::TARGET_COST => 'مخاطرة مشتركة',
        };
    }
}
