<?php

namespace Database\Seeders;

use App\Models\EndOfServiceSetting;
use Illuminate\Database\Seeder;

class EndOfServiceSettingSeeder extends Seeder
{
    /**
     * إعدادات نهاية الخدمة حسب قانون العمل الأردني
     * Jordan Labor Law End of Service Settings
     */
    public function run(): void
    {
        EndOfServiceSetting::updateOrCreate(
            ['year' => 2024],
            [
                'name' => 'إعدادات نهاية الخدمة 2024 - قانون العمل الأردني',
                'rate_per_year' => 1, // شهر واحد عن كل سنة خدمة
                'max_months' => null, // لا يوجد حد أقصى
                'calculation_basis' => 'basic_salary',
                'include_allowances' => false,
                'included_allowances' => null,
                'min_service_months' => 12, // الحد الأدنى سنة واحدة
                'prorate_partial_years' => true,
                // نسب الاستقالة حسب قانون العمل الأردني
                'resignation_rate_1_5_years' => 0.3333, // ثلث المكافأة للخدمة من 1-5 سنوات
                'resignation_rate_5_10_years' => 0.6667, // ثلثين المكافأة للخدمة من 5-10 سنوات
                'resignation_rate_over_10_years' => 1, // المكافأة كاملة لأكثر من 10 سنوات
                'dismissal_without_cause_full' => true,
                'is_active' => true,
                'notes' => 'حسب المادة 32 من قانون العمل الأردني رقم 8 لسنة 1996 وتعديلاته',
            ]
        );

        EndOfServiceSetting::updateOrCreate(
            ['year' => 2025],
            [
                'name' => 'إعدادات نهاية الخدمة 2025 - قانون العمل الأردني',
                'rate_per_year' => 1,
                'max_months' => null,
                'calculation_basis' => 'basic_salary',
                'include_allowances' => false,
                'included_allowances' => null,
                'min_service_months' => 12,
                'prorate_partial_years' => true,
                'resignation_rate_1_5_years' => 0.3333,
                'resignation_rate_5_10_years' => 0.6667,
                'resignation_rate_over_10_years' => 1,
                'dismissal_without_cause_full' => true,
                'is_active' => true,
                'notes' => 'حسب المادة 32 من قانون العمل الأردني رقم 8 لسنة 1996 وتعديلاته',
            ]
        );
    }
}
