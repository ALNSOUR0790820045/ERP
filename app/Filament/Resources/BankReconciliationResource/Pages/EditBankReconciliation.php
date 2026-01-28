<?php

namespace App\Filament\Resources\BankReconciliationResource\Pages;

use App\Filament\Resources\BankReconciliationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBankReconciliation extends EditRecord
{
    protected static string $resource = BankReconciliationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['adjusted_book_balance'] = ($data['book_balance'] ?? 0) 
            - ($data['bank_charges'] ?? 0) 
            + ($data['bank_interest'] ?? 0) 
            + ($data['other_adjustments'] ?? 0);

        $adjustedStatement = ($data['statement_balance'] ?? 0) 
            + ($data['deposits_in_transit'] ?? 0) 
            - ($data['outstanding_checks'] ?? 0);

        $data['difference'] = $data['adjusted_book_balance'] - $adjustedStatement;

        return $data;
    }
}
