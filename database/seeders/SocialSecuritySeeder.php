<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SocialSecuritySetting;

class SocialSecuritySeeder extends Seeder
{
    /**
     * إعدادات الضمان الاجتماعي الأردني
     * حسب مؤسسة الضمان الاجتماعي
     */
    public function run(): void
    {
        SocialSecuritySetting::updateOrCreate(
            ['name' => 'الضمان الاجتماعي الأردني 2024'],
            [
                'employer_rate' => 14.25,
                'employee_rate' => 7.50,
                'total_rate' => 21.75,
                'minimum_wage' => 260.000,
                'maximum_contributable_salary' => null, // لا يوجد حد أعلى حالياً
                'effective_from' => '2024-01-01',
                'effective_to' => null,
                'is_active' => true,
                'notes' => 'نسب الضمان الاجتماعي الأردني لعام 2024 - المجموع 21.75% (14.25% صاحب العمل + 7.5% العامل)',
            ]
        );

        // إعدادات سابقة للأرشيف
        SocialSecuritySetting::updateOrCreate(
            ['name' => 'الضمان الاجتماعي الأردني 2023'],
            [
                'employer_rate' => 14.25,
                'employee_rate' => 7.50,
                'total_rate' => 21.75,
                'minimum_wage' => 260.000,
                'maximum_contributable_salary' => null,
                'effective_from' => '2023-01-01',
                'effective_to' => '2023-12-31',
                'is_active' => false,
                'notes' => 'نسب الضمان الاجتماعي الأردني لعام 2023',
            ]
        );
    }
}
