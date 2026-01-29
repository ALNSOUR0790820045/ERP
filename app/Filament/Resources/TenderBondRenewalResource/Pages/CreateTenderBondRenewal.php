<?php

namespace App\Filament\Resources\TenderBondRenewalResource\Pages;

use App\Filament\Resources\TenderBondRenewalResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTenderBondRenewal extends CreateRecord
{
    protected static string $resource = TenderBondRenewalResource::class;

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
