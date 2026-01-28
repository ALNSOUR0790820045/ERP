<?php

namespace Database\Seeders;

use App\Models\Sequence;
use Illuminate\Database\Seeder;

class SequenceSeeder extends Seeder
{
    public function run(): void
    {
        $sequences = [
            [
                'code' => 'TENDER',
                'name' => 'رقم العطاء',
                'document_type' => 'tender',
                'prefix' => 'TND',
                'next_number' => 1,
                'min_digits' => 4,
                'reset_period' => 'yearly',
                'include_year' => true,
                'include_branch' => false,
            ],
            [
                'code' => 'CONTRACT',
                'name' => 'رقم العقد',
                'document_type' => 'contract',
                'prefix' => 'CNT',
                'next_number' => 1,
                'min_digits' => 4,
                'reset_period' => 'yearly',
                'include_year' => true,
                'include_branch' => false,
            ],
            [
                'code' => 'PROJECT',
                'name' => 'رقم المشروع',
                'document_type' => 'project',
                'prefix' => 'PRJ',
                'next_number' => 1,
                'min_digits' => 4,
                'reset_period' => 'yearly',
                'include_year' => true,
                'include_branch' => false,
            ],
            [
                'code' => 'INVOICE',
                'name' => 'رقم الفاتورة',
                'document_type' => 'invoice',
                'prefix' => 'INV',
                'next_number' => 1,
                'min_digits' => 5,
                'reset_period' => 'yearly',
                'include_year' => true,
                'include_branch' => true,
            ],
            [
                'code' => 'PAYMENT_CERT',
                'name' => 'رقم شهادة الدفع',
                'document_type' => 'payment_certificate',
                'prefix' => 'PC',
                'next_number' => 1,
                'min_digits' => 4,
                'reset_period' => 'yearly',
                'include_year' => true,
                'include_branch' => false,
            ],
            [
                'code' => 'PO',
                'name' => 'رقم أمر الشراء',
                'document_type' => 'purchase_order',
                'prefix' => 'PO',
                'next_number' => 1,
                'min_digits' => 5,
                'reset_period' => 'yearly',
                'include_year' => true,
                'include_branch' => true,
            ],
            [
                'code' => 'PR',
                'name' => 'رقم طلب الشراء',
                'document_type' => 'purchase_request',
                'prefix' => 'PR',
                'next_number' => 1,
                'min_digits' => 5,
                'reset_period' => 'yearly',
                'include_year' => true,
                'include_branch' => true,
            ],
            [
                'code' => 'GRN',
                'name' => 'رقم استلام البضاعة',
                'document_type' => 'goods_receipt',
                'prefix' => 'GRN',
                'next_number' => 1,
                'min_digits' => 5,
                'reset_period' => 'yearly',
                'include_year' => true,
                'include_branch' => true,
            ],
            [
                'code' => 'GIN',
                'name' => 'رقم صرف البضاعة',
                'document_type' => 'goods_issue',
                'prefix' => 'GIN',
                'next_number' => 1,
                'min_digits' => 5,
                'reset_period' => 'yearly',
                'include_year' => true,
                'include_branch' => true,
            ],
            [
                'code' => 'JV',
                'name' => 'رقم قيد اليومية',
                'document_type' => 'journal_entry',
                'prefix' => 'JV',
                'next_number' => 1,
                'min_digits' => 6,
                'reset_period' => 'yearly',
                'include_year' => true,
                'include_branch' => false,
            ],
            [
                'code' => 'EMP',
                'name' => 'رقم الموظف',
                'document_type' => 'employee',
                'prefix' => 'EMP',
                'next_number' => 1,
                'min_digits' => 4,
                'reset_period' => 'never',
                'include_year' => false,
                'include_branch' => false,
            ],
        ];

        foreach ($sequences as $sequence) {
            Sequence::firstOrCreate(
                ['code' => $sequence['code']],
                array_merge($sequence, ['current_year' => date('Y')])
            );
        }
    }
}
