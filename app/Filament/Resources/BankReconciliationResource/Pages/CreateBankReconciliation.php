<?php

namespace App\Filament\Resources\BankReconciliationResource\Pages;

use App\Filament\Resources\BankReconciliationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBankReconciliation extends CreateRecord
{
    protected static string $resource = BankReconciliationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // حساب الرصيد المعدل
        $data['adjusted_book_balance'] = ($data['book_balance'] ?? 0) 
            - ($data['bank_charges'] ?? 0) 
            + ($data['bank_interest'] ?? 0) 
            + ($data['other_adjustments'] ?? 0);

        // حساب رصيد البنك المعدل
        $adjustedStatement = ($data['statement_balance'] ?? 0) 
            + ($data['deposits_in_transit'] ?? 0) 
            - ($data['outstanding_checks'] ?? 0);

        // الفرق
        $data['difference'] = $data['adjusted_book_balance'] - $adjustedStatement;

        return $data;
    }
}
