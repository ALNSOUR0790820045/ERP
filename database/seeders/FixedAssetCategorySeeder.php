<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FixedAssetCategory;

class FixedAssetCategorySeeder extends Seeder
{
    /**
     * تصنيفات الأصول الثابتة
     */
    public function run(): void
    {
        $categories = [
            // الأراضي
            [
                'code' => 'LAND',
                'name_ar' => 'الأراضي',
                'name_en' => 'Land',
                'depreciation_method' => 'none',
                'useful_life_years' => 0,
                'is_active' => true,
            ],
            // المباني
            [
                'code' => 'BLDG',
                'name_ar' => 'المباني والإنشاءات',
                'name_en' => 'Buildings & Structures',
                'depreciation_method' => 'straight_line',
                'useful_life_years' => 25,
                'is_active' => true,
            ],
            // الآلات والمعدات
            [
                'code' => 'MACH',
                'name_ar' => 'الآلات والمعدات',
                'name_en' => 'Machinery & Equipment',
                'depreciation_method' => 'straight_line',
                'useful_life_years' => 10,
                'is_active' => true,
            ],
            // معدات البناء
            [
                'code' => 'CONST',
                'name_ar' => 'معدات البناء',
                'name_en' => 'Construction Equipment',
                'depreciation_method' => 'straight_line',
                'useful_life_years' => 7,
                'is_active' => true,
            ],
            // السيارات
            [
                'code' => 'VEH',
                'name_ar' => 'السيارات والمركبات',
                'name_en' => 'Vehicles',
                'depreciation_method' => 'straight_line',
                'useful_life_years' => 5,
                'is_active' => true,
            ],
            // الأثاث والتجهيزات
            [
                'code' => 'FURN',
                'name_ar' => 'الأثاث والتجهيزات',
                'name_en' => 'Furniture & Fixtures',
                'depreciation_method' => 'straight_line',
                'useful_life_years' => 7,
                'is_active' => true,
            ],
            // أجهزة الكمبيوتر
            [
                'code' => 'COMP',
                'name_ar' => 'أجهزة الكمبيوتر',
                'name_en' => 'Computers',
                'depreciation_method' => 'straight_line',
                'useful_life_years' => 3,
                'is_active' => true,
            ],
            // البرمجيات
            [
                'code' => 'SOFT',
                'name_ar' => 'البرمجيات',
                'name_en' => 'Software',
                'depreciation_method' => 'straight_line',
                'useful_life_years' => 3,
                'is_active' => true,
            ],
            // معدات المكتب
            [
                'code' => 'OFFEQ',
                'name_ar' => 'معدات المكتب',
                'name_en' => 'Office Equipment',
                'depreciation_method' => 'straight_line',
                'useful_life_years' => 5,
                'is_active' => true,
            ],
            // أنظمة التكييف
            [
                'code' => 'HVAC',
                'name_ar' => 'أنظمة التكييف والتبريد',
                'name_en' => 'HVAC Systems',
                'depreciation_method' => 'straight_line',
                'useful_life_years' => 10,
                'is_active' => true,
            ],
            // أدوات ومعدات يدوية
            [
                'code' => 'TOOLS',
                'name_ar' => 'الأدوات والمعدات اليدوية',
                'name_en' => 'Tools & Hand Equipment',
                'depreciation_method' => 'straight_line',
                'useful_life_years' => 3,
                'is_active' => true,
            ],
            // أصول غير ملموسة
            [
                'code' => 'INTANG',
                'name_ar' => 'الأصول غير الملموسة',
                'name_en' => 'Intangible Assets',
                'depreciation_method' => 'straight_line',
                'useful_life_years' => 10,
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            FixedAssetCategory::updateOrCreate(
                ['code' => $category['code']],
                $category
            );
        }
    }
}
