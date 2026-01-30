<?php

namespace Database\Seeders;

use App\Models\ClassificationCategory;
use App\Models\ClassificationField;
use App\Models\ClassificationSpecialty;
use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class ClassificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // مجالات التصنيف (المقاولين)
        $fields = [
            [
                'name_ar' => 'الأبنية',
                'name_en' => 'Buildings',
                'code' => 'BLDG',
                'specialties' => [
                    ['name_ar' => 'أبنية سكنية', 'name_en' => 'Residential Buildings', 'code' => 'RES'],
                    ['name_ar' => 'أبنية تجارية', 'name_en' => 'Commercial Buildings', 'code' => 'COM'],
                    ['name_ar' => 'أبنية صناعية', 'name_en' => 'Industrial Buildings', 'code' => 'IND'],
                    ['name_ar' => 'أبنية تعليمية', 'name_en' => 'Educational Buildings', 'code' => 'EDU'],
                    ['name_ar' => 'أبنية صحية', 'name_en' => 'Healthcare Buildings', 'code' => 'HLT'],
                ],
            ],
            [
                'name_ar' => 'الطرق',
                'name_en' => 'Roads',
                'code' => 'ROAD',
                'specialties' => [
                    ['name_ar' => 'طرق رئيسية', 'name_en' => 'Main Roads', 'code' => 'MAIN'],
                    ['name_ar' => 'طرق فرعية', 'name_en' => 'Secondary Roads', 'code' => 'SEC'],
                    ['name_ar' => 'جسور', 'name_en' => 'Bridges', 'code' => 'BRDG'],
                    ['name_ar' => 'أنفاق', 'name_en' => 'Tunnels', 'code' => 'TUNL'],
                ],
            ],
            [
                'name_ar' => 'المياه والصرف الصحي',
                'name_en' => 'Water & Sanitation',
                'code' => 'WATS',
                'specialties' => [
                    ['name_ar' => 'شبكات مياه', 'name_en' => 'Water Networks', 'code' => 'WNET'],
                    ['name_ar' => 'شبكات صرف صحي', 'name_en' => 'Sewage Networks', 'code' => 'SEWG'],
                    ['name_ar' => 'محطات معالجة', 'name_en' => 'Treatment Plants', 'code' => 'TRMT'],
                    ['name_ar' => 'خزانات مياه', 'name_en' => 'Water Tanks', 'code' => 'TANK'],
                ],
            ],
            [
                'name_ar' => 'الكهرباء والميكانيك',
                'name_en' => 'Electrical & Mechanical',
                'code' => 'ELMC',
                'specialties' => [
                    ['name_ar' => 'تمديدات كهربائية', 'name_en' => 'Electrical Works', 'code' => 'ELEC'],
                    ['name_ar' => 'تكييف وتبريد', 'name_en' => 'HVAC', 'code' => 'HVAC'],
                    ['name_ar' => 'مصاعد', 'name_en' => 'Elevators', 'code' => 'ELEV'],
                    ['name_ar' => 'أعمال ميكانيكية', 'name_en' => 'Mechanical Works', 'code' => 'MECH'],
                ],
            ],
        ];

        foreach ($fields as $fieldData) {
            $specialties = $fieldData['specialties'];
            unset($fieldData['specialties']);
            
            $field = ClassificationField::create($fieldData);
            
            foreach ($specialties as $specialty) {
                $specialty['classification_field_id'] = $field->id;
                ClassificationSpecialty::create($specialty);
            }
        }

        // فئات التصنيف
        $categories = [
            ['name_ar' => 'الفئة الأولى', 'name_en' => 'First Category', 'code' => '1', 'min_value' => 0, 'max_value' => 500000, 'sort_order' => 1],
            ['name_ar' => 'الفئة الثانية', 'name_en' => 'Second Category', 'code' => '2', 'min_value' => 500000, 'max_value' => 1500000, 'sort_order' => 2],
            ['name_ar' => 'الفئة الثالثة', 'name_en' => 'Third Category', 'code' => '3', 'min_value' => 1500000, 'max_value' => 5000000, 'sort_order' => 3],
            ['name_ar' => 'الفئة الرابعة', 'name_en' => 'Fourth Category', 'code' => '4', 'min_value' => 5000000, 'max_value' => 15000000, 'sort_order' => 4],
            ['name_ar' => 'الفئة الخامسة', 'name_en' => 'Fifth Category', 'code' => '5', 'min_value' => 15000000, 'max_value' => null, 'sort_order' => 5],
        ];

        foreach ($categories as $category) {
            ClassificationCategory::create($category);
        }

        // أنواع المستندات
        $documentTypes = [
            ['name_ar' => 'وثائق المناقصة', 'name_en' => 'Tender Documents', 'is_mandatory' => true, 'sort_order' => 1],
            ['name_ar' => 'المخططات', 'name_en' => 'Drawings', 'is_mandatory' => true, 'sort_order' => 2],
            ['name_ar' => 'المواصفات الفنية', 'name_en' => 'Technical Specifications', 'is_mandatory' => true, 'sort_order' => 3],
            ['name_ar' => 'جداول الكميات', 'name_en' => 'Bill of Quantities', 'is_mandatory' => true, 'sort_order' => 4],
            ['name_ar' => 'شروط العقد', 'name_en' => 'Contract Conditions', 'is_mandatory' => true, 'sort_order' => 5],
            ['name_ar' => 'نموذج العطاء', 'name_en' => 'Tender Form', 'is_mandatory' => true, 'sort_order' => 6],
            ['name_ar' => 'الضمان البنكي', 'name_en' => 'Bank Guarantee', 'is_mandatory' => false, 'sort_order' => 7],
            ['name_ar' => 'التقرير الجيوتقني', 'name_en' => 'Geotechnical Report', 'is_mandatory' => false, 'sort_order' => 8],
            ['name_ar' => 'دراسة الأثر البيئي', 'name_en' => 'Environmental Impact Study', 'is_mandatory' => false, 'sort_order' => 9],
            ['name_ar' => 'ملحقات وتعديلات', 'name_en' => 'Addenda & Amendments', 'is_mandatory' => false, 'sort_order' => 10],
        ];

        foreach ($documentTypes as $docType) {
            DocumentType::create($docType);
        }
    }
}
