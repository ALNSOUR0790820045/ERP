<?php

namespace App\Filament\Resources\TenderAlertResource\Pages;

use App\Filament\Resources\TenderAlertResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTenderAlert extends CreateRecord
{
    protected static string $resource = TenderAlertResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        return $data;
    }
}
