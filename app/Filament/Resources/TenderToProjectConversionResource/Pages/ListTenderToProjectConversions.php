<?php

namespace App\Filament\Resources\TenderToProjectConversionResource\Pages;

use App\Filament\Resources\TenderToProjectConversionResource;
use Filament\Resources\Pages\ListRecords;

class ListTenderToProjectConversions extends ListRecords
{
    protected static string $resource = TenderToProjectConversionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
