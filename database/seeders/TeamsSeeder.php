<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

class TeamsSeeder extends Seeder
{
    public function run(): void
    {
        // إنشاء فرق العمل الأساسية
        $teams = [
            [
                'name_ar' => 'فريق رصد العطاءات',
                'name_en' => 'Tender Monitoring Team',
                'code' => 'tender_monitoring',
                'type' => 'tender',
                'description' => 'فريق متخصص في رصد ومتابعة العطاءات الجديدة',
            ],
            [
                'name_ar' => 'فريق دراسة العطاءات',
                'name_en' => 'Tender Analysis Team',
                'code' => 'tender_analysis',
                'type' => 'tender',
                'description' => 'فريق متخصص في دراسة وتحليل العطاءات',
            ],
            [
                'name_ar' => 'فريق التسعير - مشاريع الطرق',
                'name_en' => 'Pricing Team - Roads',
                'code' => 'pricing_roads',
                'type' => 'pricing',
                'description' => 'فريق تسعير متخصص في مشاريع الطرق والجسور',
            ],
            [
                'name_ar' => 'فريق التسعير - مشاريع المباني',
                'name_en' => 'Pricing Team - Buildings',
                'code' => 'pricing_buildings',
                'type' => 'pricing',
                'description' => 'فريق تسعير متخصص في مشاريع المباني',
            ],
            [
                'name_ar' => 'الفريق الفني',
                'name_en' => 'Technical Team',
                'code' => 'technical',
                'type' => 'technical',
                'description' => 'الفريق الفني لمراجعة المواصفات والمتطلبات',
            ],
            [
                'name_ar' => 'فريق تقديم العطاءات',
                'name_en' => 'Tender Submission Team',
                'code' => 'tender_submission',
                'type' => 'tender',
                'description' => 'فريق متخصص في تجهيز وتقديم العطاءات',
            ],
        ];

        foreach ($teams as $teamData) {
            Team::updateOrCreate(
                ['code' => $teamData['code']],
                array_merge($teamData, [
                    'is_active' => true,
                    'created_by' => 1,
                ])
            );
        }

        $this->command->info('تم إنشاء ' . count($teams) . ' فريق عمل');
    }
}
