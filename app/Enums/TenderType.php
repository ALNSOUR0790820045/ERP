<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasDescription;

/**
 * أنواع العطاءات حسب الوثائق القياسية الأردنية
 */
enum TenderType: string implements HasLabel, HasColor, HasDescription
{
    // 1. الوثيقة القياسية للأشغال الصغيرة (< 500,000 دينار)
    case SMALL_WORKS = 'small_works';
    
    // 2. الوثيقة القياسية للأشغال الكبيرة (> 500,000 دينار)
    case LARGE_WORKS = 'large_works';
    
    // 3. طلبات الشراء (10,000 - 20,000 دينار) - بدون كفالة
    case PURCHASE_REQUEST = 'purchase_request';
    
    // 4. طلبات اللوازم (< 5,000 دينار) - بدون إجراءات
    case SUPPLIES_REQUEST = 'supplies_request';
    
    // 5. خدمات المقاولات للأفراد
    case INDIVIDUAL_SERVICES = 'individual_services';
    
    // 6. مقاولة الباطن
    case SUBCONTRACTING = 'subcontracting';

    public function getLabel(): string
    {
        return match($this) {
            self::SMALL_WORKS => 'أشغال صغيرة (< 500,000)',
            self::LARGE_WORKS => 'أشغال كبيرة (> 500,000)',
            self::PURCHASE_REQUEST => 'طلب شراء',
            self::SUPPLIES_REQUEST => 'طلب لوازم',
            self::INDIVIDUAL_SERVICES => 'خدمات للأفراد',
            self::SUBCONTRACTING => 'مقاولة باطن',
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }

    public function getColor(): string
    {
        return match($this) {
            self::SMALL_WORKS => 'info',
            self::LARGE_WORKS => 'primary',
            self::PURCHASE_REQUEST => 'success',
            self::SUPPLIES_REQUEST => 'gray',
            self::INDIVIDUAL_SERVICES => 'warning',
            self::SUBCONTRACTING => 'danger',
        };
    }

    public function getDescription(): string
    {
        return match($this) {
            self::SMALL_WORKS => 'الوثيقة القياسية لشراء الأشغال الحكومية التي لا تتجاوز قيمتها التقديرية (500,000) دينار',
            self::LARGE_WORKS => 'الوثيقة القياسية لشراء الأشغال الحكومية التي تتجاوز قيمتها التقديرية (500,000) دينار',
            self::PURCHASE_REQUEST => 'طلبات شراء من المالك (10,000 - 20,000 دينار) - بدون كفالة دخول عطاء',
            self::SUPPLIES_REQUEST => 'طلبات لوازم (أقل من 5,000 دينار) - بدون إجراءات أو كفالات',
            self::INDIVIDUAL_SERVICES => 'خدمات المقاولات للأشخاص العاديين (صيانة، إنشاء، إلخ)',
            self::SUBCONTRACTING => 'مقاولة الباطن من شركة مقاول رئيسي - مرنة في الإجراءات',
        };
    }

    /**
     * هل يتطلب هذا النوع كفالة دخول عطاء؟
     */
    public function requiresBidBond(): bool
    {
        return match($this) {
            self::SMALL_WORKS, self::LARGE_WORKS => true,
            self::PURCHASE_REQUEST, self::SUPPLIES_REQUEST, self::INDIVIDUAL_SERVICES, self::SUBCONTRACTING => false,
        };
    }

    /**
     * هل يتطلب هذا النوع إجراءات رسمية؟
     */
    public function requiresFormalProcedures(): bool
    {
        return match($this) {
            self::SMALL_WORKS, self::LARGE_WORKS, self::PURCHASE_REQUEST => true,
            self::SUPPLIES_REQUEST, self::INDIVIDUAL_SERVICES, self::SUBCONTRACTING => false,
        };
    }

    /**
     * الحد الأدنى للقيمة التقديرية بالدينار
     */
    public function getMinValue(): ?float
    {
        return match($this) {
            self::LARGE_WORKS => 500000,
            self::PURCHASE_REQUEST => 10000,
            default => null,
        };
    }

    /**
     * الحد الأقصى للقيمة التقديرية بالدينار
     */
    public function getMaxValue(): ?float
    {
        return match($this) {
            self::SMALL_WORKS => 500000,
            self::PURCHASE_REQUEST => 20000,
            self::SUPPLIES_REQUEST => 5000,
            default => null,
        };
    }
}
