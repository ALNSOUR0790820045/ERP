<?php

namespace App\Filament\Resources\TenderToProjectConversionResource\Pages;

use App\Filament\Resources\TenderToProjectConversionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTenderToProjectConversion extends CreateRecord
{
    protected static string $resource = TenderToProjectConversionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['converted_by'] = auth()->id();
        return $data;
    }
}
