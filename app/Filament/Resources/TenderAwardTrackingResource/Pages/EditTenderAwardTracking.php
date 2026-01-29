<?php

namespace App\Filament\Resources\TenderAwardTrackingResource\Pages;

use App\Filament\Resources\TenderAwardTrackingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTenderAwardTracking extends EditRecord
{
    protected static string $resource = TenderAwardTrackingResource::class;

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
