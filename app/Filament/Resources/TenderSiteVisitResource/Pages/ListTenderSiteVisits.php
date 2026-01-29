<?php

namespace App\Filament\Resources\TenderSiteVisitResource\Pages;

use App\Filament\Resources\TenderSiteVisitResource;
use Filament\Resources\Pages\ListRecords;

class ListTenderSiteVisits extends ListRecords
{
    protected static string $resource = TenderSiteVisitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
