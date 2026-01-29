<?php

namespace App\Filament\Resources\TenderDiscoveryResource\Pages;

use App\Filament\Resources\TenderDiscoveryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTenderDiscovery extends EditRecord
{
    protected static string $resource = TenderDiscoveryResource::class;

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
