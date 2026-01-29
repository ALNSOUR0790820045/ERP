<?php

namespace App\Filament\Resources\TenderBondRenewalResource\Pages;

use App\Filament\Resources\TenderBondRenewalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTenderBondRenewal extends EditRecord
{
    protected static string $resource = TenderBondRenewalResource::class;

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
