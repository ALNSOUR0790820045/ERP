<?php

namespace App\Filament\Resources\TenderSiteVisitResource\Pages;

use App\Filament\Resources\TenderSiteVisitResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTenderSiteVisit extends EditRecord
{
    protected static string $resource = TenderSiteVisitResource::class;

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
