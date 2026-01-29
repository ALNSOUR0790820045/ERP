<?php

namespace App\Filament\Resources\TenderDiscoveryResource\Pages;

use App\Filament\Resources\TenderDiscoveryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTenderDiscovery extends CreateRecord
{
    protected static string $resource = TenderDiscoveryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['discovered_by'] = auth()->id();
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
