<?php

namespace App\Filament\Resources\TenderAlertResource\Pages;

use App\Filament\Resources\TenderAlertResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTenderAlert extends EditRecord
{
    protected static string $resource = TenderAlertResource::class;

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
