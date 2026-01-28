<?php

namespace App\Filament\Resources\ContractAdvancePaymentResource\Pages;

use App\Filament\Resources\ContractAdvancePaymentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateContractAdvancePayment extends CreateRecord
{
    protected static string $resource = ContractAdvancePaymentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['balance_amount'] = $data['advance_amount'] ?? 0;
        $data['recovered_amount'] = 0;
        return $data;
    }
}
