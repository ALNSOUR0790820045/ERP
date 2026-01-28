<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\ProjectType;
use App\Models\Specialization;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class LookupSeeder extends Seeder
{
    public function run(): void
    {
        // العملات
        $currencies = [
            ['code' => 'JOD', 'symbol' => 'د.أ', 'name_ar' => 'دينار أردني', 'name_en' => 'Jordanian Dinar', 'exchange_rate' => 1, 'is_active' => true],
            ['code' => 'USD', 'symbol' => '$', 'name_ar' => 'دولار أمريكي', 'name_en' => 'US Dollar', 'exchange_rate' => 0.71, 'is_active' => true],
            ['code' => 'EUR', 'symbol' => '€', 'name_ar' => 'يورو', 'name_en' => 'Euro', 'exchange_rate' => 0.77, 'is_active' => true],
            ['code' => 'SAR', 'symbol' => 'ر.س', 'name_ar' => 'ريال سعودي', 'name_en' => 'Saudi Riyal', 'exchange_rate' => 0.19, 'is_active' => true],
            ['code' => 'AED', 'symbol' => 'د.إ', 'name_ar' => 'درهم إماراتي', 'name_en' => 'UAE Dirham', 'exchange_rate' => 0.19, 'is_active' => true],
        ];

        foreach ($currencies as $currency) {
            Currency::firstOrCreate(['code' => $currency['code']], $currency);
        }

        // أنواع المشاريع
        $projectTypes = [
            ['code' => 'BUILDING', 'name_ar' => 'مباني', 'name_en' => 'Buildings', 'is_active' => true],
            ['code' => 'ROADS', 'name_ar' => 'طرق', 'name_en' => 'Roads', 'is_active' => true],
            ['code' => 'WATER', 'name_ar' => 'مياه وصرف صحي', 'name_en' => 'Water & Sewage', 'is_active' => true],
            ['code' => 'ELECTRO', 'name_ar' => 'كهروميكانيك', 'name_en' => 'Electromechanical', 'is_active' => true],
            ['code' => 'INFRA', 'name_ar' => 'بنية تحتية', 'name_en' => 'Infrastructure', 'is_active' => true],
            ['code' => 'INDUSTRIAL', 'name_ar' => 'صناعي', 'name_en' => 'Industrial', 'is_active' => true],
            ['code' => 'MAINTENANCE', 'name_ar' => 'صيانة', 'name_en' => 'Maintenance', 'is_active' => true],
            ['code' => 'RENOVATION', 'name_ar' => 'ترميم', 'name_en' => 'Renovation', 'is_active' => true],
        ];

        foreach ($projectTypes as $type) {
            ProjectType::firstOrCreate(['code' => $type['code']], $type);
        }

        // التخصصات
        $specializations = [
            ['code' => 'CIVIL', 'name_ar' => 'أعمال مدنية', 'name_en' => 'Civil Works', 'is_active' => true],
            ['code' => 'STRUCTURAL', 'name_ar' => 'أعمال إنشائية', 'name_en' => 'Structural Works', 'is_active' => true],
            ['code' => 'MEP', 'name_ar' => 'أعمال ميكانيكية وكهربائية', 'name_en' => 'MEP Works', 'is_active' => true],
            ['code' => 'FINISHING', 'name_ar' => 'أعمال تشطيبات', 'name_en' => 'Finishing Works', 'is_active' => true],
            ['code' => 'LANDSCAPING', 'name_ar' => 'أعمال تنسيق مواقع', 'name_en' => 'Landscaping', 'is_active' => true],
            ['code' => 'HVAC', 'name_ar' => 'تكييف وتهوية', 'name_en' => 'HVAC', 'is_active' => true],
            ['code' => 'PLUMBING', 'name_ar' => 'سباكة', 'name_en' => 'Plumbing', 'is_active' => true],
            ['code' => 'ELECTRICAL', 'name_ar' => 'كهرباء', 'name_en' => 'Electrical', 'is_active' => true],
            ['code' => 'FIREFIGHTING', 'name_ar' => 'إطفاء حريق', 'name_en' => 'Firefighting', 'is_active' => true],
        ];

        foreach ($specializations as $spec) {
            Specialization::firstOrCreate(['code' => $spec['code']], $spec);
        }

        // الوحدات
        $units = [
            // وحدات الطول
            ['symbol' => 'M', 'name_ar' => 'متر', 'name_en' => 'Meter', 'type' => 'length', 'is_active' => true],
            ['symbol' => 'CM', 'name_ar' => 'سنتيمتر', 'name_en' => 'Centimeter', 'type' => 'length', 'is_active' => true],
            ['symbol' => 'KM', 'name_ar' => 'كيلومتر', 'name_en' => 'Kilometer', 'type' => 'length', 'is_active' => true],
            
            // وحدات المساحة
            ['symbol' => 'M2', 'name_ar' => 'متر مربع', 'name_en' => 'Square Meter', 'type' => 'area', 'is_active' => true],
            
            // وحدات الحجم
            ['symbol' => 'M3', 'name_ar' => 'متر مكعب', 'name_en' => 'Cubic Meter', 'type' => 'volume', 'is_active' => true],
            ['symbol' => 'L', 'name_ar' => 'لتر', 'name_en' => 'Liter', 'type' => 'volume', 'is_active' => true],
            
            // وحدات الوزن
            ['symbol' => 'KG', 'name_ar' => 'كيلوغرام', 'name_en' => 'Kilogram', 'type' => 'weight', 'is_active' => true],
            ['symbol' => 'TON', 'name_ar' => 'طن', 'name_en' => 'Ton', 'type' => 'weight', 'is_active' => true],
            
            // وحدات العد
            ['symbol' => 'NO', 'name_ar' => 'عدد', 'name_en' => 'Number', 'type' => 'count', 'is_active' => true],
            ['symbol' => 'SET', 'name_ar' => 'طقم', 'name_en' => 'Set', 'type' => 'count', 'is_active' => true],
            ['symbol' => 'PAIR', 'name_ar' => 'زوج', 'name_en' => 'Pair', 'type' => 'count', 'is_active' => true],
            
            // وحدات الوقت
            ['symbol' => 'HR', 'name_ar' => 'ساعة', 'name_en' => 'Hour', 'type' => 'time', 'is_active' => true],
            ['symbol' => 'DAY', 'name_ar' => 'يوم', 'name_en' => 'Day', 'type' => 'time', 'is_active' => true],
            ['symbol' => 'WEEK', 'name_ar' => 'أسبوع', 'name_en' => 'Week', 'type' => 'time', 'is_active' => true],
            ['symbol' => 'MONTH', 'name_ar' => 'شهر', 'name_en' => 'Month', 'type' => 'time', 'is_active' => true],
            
            // وحدات أخرى
            ['symbol' => 'LS', 'name_ar' => 'مقطوعية', 'name_en' => 'Lump Sum', 'type' => 'other', 'is_active' => true],
            ['symbol' => 'ML', 'name_ar' => 'متر طولي', 'name_en' => 'Linear Meter', 'type' => 'length', 'is_active' => true],
        ];

        foreach ($units as $unit) {
            Unit::firstOrCreate(['symbol' => $unit['symbol']], $unit);
        }
    }
}
