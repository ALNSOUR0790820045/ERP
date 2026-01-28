<?php

namespace Database\Seeders;

use App\Enums\OwnerType;
use App\Models\Consultant;
use App\Models\Owner;
use Illuminate\Database\Seeder;

class OwnerConsultantSeeder extends Seeder
{
    public function run(): void
    {
        // الملاك الحكوميين
        $governmentOwners = [
            ['name_ar' => 'وزارة الأشغال العامة والإسكان', 'name_en' => 'Ministry of Public Works and Housing', 'type' => OwnerType::GOVERNMENT],
            ['name_ar' => 'أمانة عمان الكبرى', 'name_en' => 'Greater Amman Municipality', 'type' => OwnerType::GOVERNMENT],
            ['name_ar' => 'سلطة المياه', 'name_en' => 'Water Authority', 'type' => OwnerType::GOVERNMENT],
            ['name_ar' => 'وزارة الصحة', 'name_en' => 'Ministry of Health', 'type' => OwnerType::GOVERNMENT],
            ['name_ar' => 'وزارة التربية والتعليم', 'name_en' => 'Ministry of Education', 'type' => OwnerType::GOVERNMENT],
            ['name_ar' => 'الجامعة الأردنية', 'name_en' => 'University of Jordan', 'type' => OwnerType::GOVERNMENT],
            ['name_ar' => 'جامعة العلوم والتكنولوجيا', 'name_en' => 'JUST', 'type' => OwnerType::GOVERNMENT],
            ['name_ar' => 'وزارة الدفاع', 'name_en' => 'Ministry of Defense', 'type' => OwnerType::GOVERNMENT],
        ];

        foreach ($governmentOwners as $owner) {
            Owner::firstOrCreate(
                ['name_ar' => $owner['name_ar']],
                array_merge($owner, ['is_active' => true])
            );
        }

        // الملاك الخاصين
        $privateOwners = [
            ['name_ar' => 'شركة الخليج للاستثمار', 'name_en' => 'Gulf Investment Company', 'type' => OwnerType::PRIVATE],
            ['name_ar' => 'مجموعة طلال أبو غزالة', 'name_en' => 'Talal Abu-Ghazaleh Group', 'type' => OwnerType::PRIVATE],
            ['name_ar' => 'البنك العربي', 'name_en' => 'Arab Bank', 'type' => OwnerType::PRIVATE],
            ['name_ar' => 'شركة زين الأردن', 'name_en' => 'Zain Jordan', 'type' => OwnerType::PRIVATE],
        ];

        foreach ($privateOwners as $owner) {
            Owner::firstOrCreate(
                ['name_ar' => $owner['name_ar']],
                array_merge($owner, ['is_active' => true])
            );
        }

        // الملاك الدوليين
        $internationalOwners = [
            ['name_ar' => 'البنك الدولي', 'name_en' => 'World Bank', 'type' => OwnerType::INTERNATIONAL],
            ['name_ar' => 'الوكالة الأمريكية للتنمية الدولية', 'name_en' => 'USAID', 'type' => OwnerType::INTERNATIONAL],
            ['name_ar' => 'الاتحاد الأوروبي', 'name_en' => 'European Union', 'type' => OwnerType::INTERNATIONAL],
            ['name_ar' => 'برنامج الأمم المتحدة الإنمائي', 'name_en' => 'UNDP', 'type' => OwnerType::INTERNATIONAL],
        ];

        foreach ($internationalOwners as $owner) {
            Owner::firstOrCreate(
                ['name_ar' => $owner['name_ar']],
                array_merge($owner, ['is_active' => true])
            );
        }

        // الاستشاريين
        $consultants = [
            ['name_ar' => 'دار الهندسة - شاعر ومشاركوه', 'name_en' => 'Dar Al-Handasah', 'is_active' => true],
            ['name_ar' => 'المكتب العربي للتصاميم الهندسية', 'name_en' => 'Arab Engineering Bureau', 'is_active' => true],
            ['name_ar' => 'كونسلت', 'name_en' => 'Consult', 'is_active' => true],
            ['name_ar' => 'إنجاز للاستشارات الهندسية', 'name_en' => 'Enjaz Engineering Consultants', 'is_active' => true],
            ['name_ar' => 'مكتب المهندس محمد الرواشدة', 'name_en' => 'Eng. Mohammad Rawashdeh Office', 'is_active' => true],
            ['name_ar' => 'إي سي جي', 'name_en' => 'ECG', 'is_active' => true],
            ['name_ar' => 'تي إي سي', 'name_en' => 'TEC', 'is_active' => true],
        ];

        foreach ($consultants as $consultant) {
            Consultant::firstOrCreate(
                ['name_ar' => $consultant['name_ar']],
                $consultant
            );
        }
    }
}
