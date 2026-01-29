<?php

namespace App\Filament\Resources\TenderSourceResource\Pages;

use App\Filament\Resources\TenderSourceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTenderSource extends EditRecord
{
    protected static string $resource = TenderSourceResource::class;

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
