<?php

namespace App\Filament\Resources\TenderAwardTrackingResource\Pages;

use App\Filament\Resources\TenderAwardTrackingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTenderAwardTracking extends CreateRecord
{
    protected static string $resource = TenderAwardTrackingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
