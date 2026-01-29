<?php

namespace App\Filament\Resources\TenderToProjectConversionResource\Pages;

use App\Filament\Resources\TenderToProjectConversionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTenderToProjectConversion extends EditRecord
{
    protected static string $resource = TenderToProjectConversionResource::class;

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
}
