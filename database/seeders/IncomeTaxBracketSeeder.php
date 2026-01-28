<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\IncomeTaxBracket;

class IncomeTaxBracketSeeder extends Seeder
{
    /**
     * شرائح ضريبة الدخل الأردنية
     * حسب قانون ضريبة الدخل الأردني
     */
    public function run(): void
    {
        // شرائح ضريبة الدخل للأفراد 2024
        $individualBrackets = [
            [
                'name' => 'الشريحة الأولى - معفاة',
                'year' => 2024,
                'taxpayer_type' => 'individual',
                'from_amount' => 0,
                'to_amount' => 10000,
                'rate' => 0,
                'fixed_amount' => 0,
                'exemption_amount' => 10000,
            ],
            [
                'name' => 'الشريحة الثانية - 5%',
                'year' => 2024,
                'taxpayer_type' => 'individual',
                'from_amount' => 10001,
                'to_amount' => 15000,
                'rate' => 5,
                'fixed_amount' => 0,
                'exemption_amount' => 0,
            ],
            [
                'name' => 'الشريحة الثالثة - 10%',
                'year' => 2024,
                'taxpayer_type' => 'individual',
                'from_amount' => 15001,
                'to_amount' => 20000,
                'rate' => 10,
                'fixed_amount' => 250,
                'exemption_amount' => 0,
            ],
            [
                'name' => 'الشريحة الرابعة - 15%',
                'year' => 2024,
                'taxpayer_type' => 'individual',
                'from_amount' => 20001,
                'to_amount' => 25000,
                'rate' => 15,
                'fixed_amount' => 750,
                'exemption_amount' => 0,
            ],
            [
                'name' => 'الشريحة الخامسة - 20%',
                'year' => 2024,
                'taxpayer_type' => 'individual',
                'from_amount' => 25001,
                'to_amount' => 30000,
                'rate' => 20,
                'fixed_amount' => 1500,
                'exemption_amount' => 0,
            ],
            [
                'name' => 'الشريحة السادسة - 25%',
                'year' => 2024,
                'taxpayer_type' => 'individual',
                'from_amount' => 30001,
                'to_amount' => null,
                'rate' => 25,
                'fixed_amount' => 2500,
                'exemption_amount' => 0,
            ],
        ];

        // شرائح معيل الأسرة 2024
        $familyBrackets = [
            [
                'name' => 'الشريحة الأولى - معفاة (أسرة)',
                'year' => 2024,
                'taxpayer_type' => 'family',
                'from_amount' => 0,
                'to_amount' => 20000,
                'rate' => 0,
                'fixed_amount' => 0,
                'exemption_amount' => 20000,
            ],
            [
                'name' => 'الشريحة الثانية - 5% (أسرة)',
                'year' => 2024,
                'taxpayer_type' => 'family',
                'from_amount' => 20001,
                'to_amount' => 25000,
                'rate' => 5,
                'fixed_amount' => 0,
                'exemption_amount' => 0,
            ],
            [
                'name' => 'الشريحة الثالثة - 10% (أسرة)',
                'year' => 2024,
                'taxpayer_type' => 'family',
                'from_amount' => 25001,
                'to_amount' => 30000,
                'rate' => 10,
                'fixed_amount' => 250,
                'exemption_amount' => 0,
            ],
            [
                'name' => 'الشريحة الرابعة - 15% (أسرة)',
                'year' => 2024,
                'taxpayer_type' => 'family',
                'from_amount' => 30001,
                'to_amount' => 35000,
                'rate' => 15,
                'fixed_amount' => 750,
                'exemption_amount' => 0,
            ],
            [
                'name' => 'الشريحة الخامسة - 20% (أسرة)',
                'year' => 2024,
                'taxpayer_type' => 'family',
                'from_amount' => 35001,
                'to_amount' => 40000,
                'rate' => 20,
                'fixed_amount' => 1500,
                'exemption_amount' => 0,
            ],
            [
                'name' => 'الشريحة السادسة - 25% (أسرة)',
                'year' => 2024,
                'taxpayer_type' => 'family',
                'from_amount' => 40001,
                'to_amount' => null,
                'rate' => 25,
                'fixed_amount' => 2500,
                'exemption_amount' => 0,
            ],
        ];

        foreach (array_merge($individualBrackets, $familyBrackets) as $bracket) {
            IncomeTaxBracket::updateOrCreate(
                [
                    'year' => $bracket['year'],
                    'taxpayer_type' => $bracket['taxpayer_type'],
                    'from_amount' => $bracket['from_amount'],
                ],
                array_merge($bracket, ['is_active' => true])
            );
        }
    }
}
