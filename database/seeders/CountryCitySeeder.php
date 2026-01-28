<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Country;
use Illuminate\Database\Seeder;

class CountryCitySeeder extends Seeder
{
    public function run(): void
    {
        // الدول
        $countries = [
            ['code' => 'JO', 'name_ar' => 'الأردن', 'name_en' => 'Jordan', 'phone_code' => '+962', 'currency_code' => 'JOD'],
            ['code' => 'SA', 'name_ar' => 'السعودية', 'name_en' => 'Saudi Arabia', 'phone_code' => '+966', 'currency_code' => 'SAR'],
            ['code' => 'AE', 'name_ar' => 'الإمارات', 'name_en' => 'UAE', 'phone_code' => '+971', 'currency_code' => 'AED'],
            ['code' => 'KW', 'name_ar' => 'الكويت', 'name_en' => 'Kuwait', 'phone_code' => '+965', 'currency_code' => 'KWD'],
            ['code' => 'QA', 'name_ar' => 'قطر', 'name_en' => 'Qatar', 'phone_code' => '+974', 'currency_code' => 'QAR'],
            ['code' => 'BH', 'name_ar' => 'البحرين', 'name_en' => 'Bahrain', 'phone_code' => '+973', 'currency_code' => 'BHD'],
            ['code' => 'OM', 'name_ar' => 'عُمان', 'name_en' => 'Oman', 'phone_code' => '+968', 'currency_code' => 'OMR'],
            ['code' => 'EG', 'name_ar' => 'مصر', 'name_en' => 'Egypt', 'phone_code' => '+20', 'currency_code' => 'EGP'],
            ['code' => 'IQ', 'name_ar' => 'العراق', 'name_en' => 'Iraq', 'phone_code' => '+964', 'currency_code' => 'IQD'],
            ['code' => 'LB', 'name_ar' => 'لبنان', 'name_en' => 'Lebanon', 'phone_code' => '+961', 'currency_code' => 'LBP'],
            ['code' => 'PS', 'name_ar' => 'فلسطين', 'name_en' => 'Palestine', 'phone_code' => '+970', 'currency_code' => 'ILS'],
            ['code' => 'SY', 'name_ar' => 'سوريا', 'name_en' => 'Syria', 'phone_code' => '+963', 'currency_code' => 'SYP'],
        ];

        foreach ($countries as $country) {
            Country::firstOrCreate(['code' => $country['code']], array_merge($country, ['is_active' => true]));
        }

        // المدن الأردنية
        $jordan = Country::where('code', 'JO')->first();
        if ($jordan) {
            $jordanCities = [
                ['name_ar' => 'عمان', 'name_en' => 'Amman'],
                ['name_ar' => 'إربد', 'name_en' => 'Irbid'],
                ['name_ar' => 'الزرقاء', 'name_en' => 'Zarqa'],
                ['name_ar' => 'العقبة', 'name_en' => 'Aqaba'],
                ['name_ar' => 'السلط', 'name_en' => 'Salt'],
                ['name_ar' => 'المفرق', 'name_en' => 'Mafraq'],
                ['name_ar' => 'جرش', 'name_en' => 'Jerash'],
                ['name_ar' => 'عجلون', 'name_en' => 'Ajloun'],
                ['name_ar' => 'الكرك', 'name_en' => 'Karak'],
                ['name_ar' => 'معان', 'name_en' => 'Maan'],
                ['name_ar' => 'الطفيلة', 'name_en' => 'Tafilah'],
                ['name_ar' => 'مادبا', 'name_en' => 'Madaba'],
            ];

            foreach ($jordanCities as $city) {
                City::firstOrCreate(
                    ['country_id' => $jordan->id, 'name_ar' => $city['name_ar']],
                    array_merge($city, ['country_id' => $jordan->id, 'is_active' => true])
                );
            }
        }

        // المدن السعودية
        $saudi = Country::where('code', 'SA')->first();
        if ($saudi) {
            $saudiCities = [
                ['name_ar' => 'الرياض', 'name_en' => 'Riyadh'],
                ['name_ar' => 'جدة', 'name_en' => 'Jeddah'],
                ['name_ar' => 'مكة المكرمة', 'name_en' => 'Mecca'],
                ['name_ar' => 'المدينة المنورة', 'name_en' => 'Medina'],
                ['name_ar' => 'الدمام', 'name_en' => 'Dammam'],
            ];

            foreach ($saudiCities as $city) {
                City::firstOrCreate(
                    ['country_id' => $saudi->id, 'name_ar' => $city['name_ar']],
                    array_merge($city, ['country_id' => $saudi->id, 'is_active' => true])
                );
            }
        }

        // المدن الإماراتية
        $uae = Country::where('code', 'AE')->first();
        if ($uae) {
            $uaeCities = [
                ['name_ar' => 'دبي', 'name_en' => 'Dubai'],
                ['name_ar' => 'أبوظبي', 'name_en' => 'Abu Dhabi'],
                ['name_ar' => 'الشارقة', 'name_en' => 'Sharjah'],
            ];

            foreach ($uaeCities as $city) {
                City::firstOrCreate(
                    ['country_id' => $uae->id, 'name_ar' => $city['name_ar']],
                    array_merge($city, ['country_id' => $uae->id, 'is_active' => true])
                );
            }
        }
    }
}
