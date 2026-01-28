<?php

namespace App\Filament\Resources\TenderBidDataSheetResource\Pages;

use App\Filament\Resources\TenderBidDataSheetResource;
use Filament\Resources\Pages\ListRecords;

class ListTenderBidDataSheets extends ListRecords
{
    protected static string $resource = TenderBidDataSheetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
