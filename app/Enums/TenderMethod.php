<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasDescription;

/**
 * أساليب طرح العطاءات حسب نظام المشتريات الحكومية الأردني
 */
enum TenderMethod: string implements HasLabel, HasColor, HasDescription
{
    // العطاء العلني (العام) - مفتوح لجميع المناقصين المؤهلين
    case PUBLIC = 'public';
    
    // العطاء المحدود - لمناقصين معينين بالدعوة
    case LIMITED = 'limited';
    
    // الشراء المباشر - من مورد واحد
    case DIRECT = 'direct';
    
    // العطاء على مرحلتين - فني ثم مالي
    case TWO_STAGE = 'two_stage';
    
    // التأهيل المسبق - تأهيل قبل تقديم العروض
    case PREQUALIFICATION = 'prequalification';
    
    // طلب عروض أسعار (RFQ) - إجراء مبسط
    case RFQ = 'rfq';
    
    // المناقصة الخاصة - بالدعوة لمناقصين محددين
    case PRIVATE = 'private';

    public function getLabel(): string
    {
        return match($this) {
            self::PUBLIC => 'عطاء علني (عام)',
            self::LIMITED => 'عطاء محدود',
            self::DIRECT => 'شراء مباشر',
            self::TWO_STAGE => 'عطاء على مرحلتين',
            self::PREQUALIFICATION => 'تأهيل مسبق',
            self::RFQ => 'طلب عروض أسعار',
            self::PRIVATE => 'مناقصة خاصة بالدعوة',
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }

    public function getColor(): string
    {
        return match($this) {
            self::PUBLIC => 'success',
            self::LIMITED => 'warning',
            self::DIRECT => 'danger',
            self::TWO_STAGE => 'info',
            self::PREQUALIFICATION => 'primary',
            self::RFQ => 'gray',
            self::PRIVATE => 'warning',
        };
    }

    public function getDescription(): string
    {
        return match($this) {
            self::PUBLIC => 'عطاء عام مفتوح لجميع المناقصين المؤهلين - يُنشر في الجريدة الرسمية والصحف',
            self::LIMITED => 'عطاء محدود لمناقصين معينين يتم دعوتهم - للمشتريات المتخصصة',
            self::DIRECT => 'شراء مباشر من مورد واحد - للحالات الطارئة أو الاحتكارية',
            self::TWO_STAGE => 'عطاء على مرحلتين: المرحلة الفنية ثم المرحلة المالية - للمشاريع المعقدة',
            self::PREQUALIFICATION => 'تأهيل مسبق للمناقصين قبل تقديم العروض - للمشاريع الكبيرة',
            self::RFQ => 'طلب عروض أسعار مبسط - للمشتريات الصغيرة والمتوسطة',
            self::PRIVATE => 'مناقصة خاصة بالدعوة لمناقصين محددين',
        };
    }

    /**
     * هل يتطلب هذا الأسلوب النشر في الجريدة الرسمية؟
     */
    public function requiresOfficialPublication(): bool
    {
        return match($this) {
            self::PUBLIC, self::TWO_STAGE, self::PREQUALIFICATION => true,
            self::LIMITED, self::DIRECT, self::RFQ, self::PRIVATE => false,
        };
    }

    /**
     * هل يتطلب هذا الأسلوب إعلان صحفي؟
     */
    public function requiresNewspaperAd(): bool
    {
        return match($this) {
            self::PUBLIC, self::TWO_STAGE, self::PREQUALIFICATION => true,
            self::LIMITED, self::DIRECT, self::RFQ, self::PRIVATE => false,
        };
    }
}
