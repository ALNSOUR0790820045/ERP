<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum FidicType: string implements HasLabel
{
    case RED_BOOK = 'red_book';
    case YELLOW_BOOK = 'yellow_book';
    case SILVER_BOOK = 'silver_book';
    case GREEN_BOOK = 'green_book';
    case WHITE_BOOK = 'white_book';
    case ORANGE_BOOK = 'orange_book';
    case OTHER = 'other';

    public function getLabel(): string
    {
        return match($this) {
            self::RED_BOOK => 'الكتاب الأحمر - إنشاءات',
            self::YELLOW_BOOK => 'الكتاب الأصفر - تصميم وبناء',
            self::SILVER_BOOK => 'الكتاب الفضي - EPC',
            self::GREEN_BOOK => 'الكتاب الأخضر - مشاريع صغيرة',
            self::WHITE_BOOK => 'الكتاب الأبيض - استشارات',
            self::ORANGE_BOOK => 'الكتاب البرتقالي - تصميم وبناء وتشغيل',
            self::OTHER => 'أخرى',
        };
    }

    public function getDescription(): string
    {
        return match($this) {
            self::RED_BOOK => 'تصميم من صاحب العمل، المقاول ينفذ فقط',
            self::YELLOW_BOOK => 'المقاول يصمم وينفذ، مبلغ مقطوع',
            self::SILVER_BOOK => 'تسليم مفتاح، معظم المخاطر على المقاول',
            self::GREEN_BOOK => 'للمشاريع الصغيرة، إجراءات مبسطة',
            self::WHITE_BOOK => 'عقود الاستشارات الهندسية',
            self::ORANGE_BOOK => 'تصميم وبناء وتشغيل',
            self::OTHER => 'نوع عقد آخر',
        };
    }
}
