<?php

namespace App\Filament\Resources\TenderBondWithdrawalResource\Pages;

use App\Filament\Resources\TenderBondWithdrawalResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTenderBondWithdrawal extends CreateRecord
{
    protected static string $resource = TenderBondWithdrawalResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['requested_by'] = auth()->id();
        return $data;
    }
}
