<?php

namespace App\Filament\Resources\TenderSiteVisitResource\Pages;

use App\Filament\Resources\TenderSiteVisitResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTenderSiteVisit extends CreateRecord
{
    protected static string $resource = TenderSiteVisitResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
