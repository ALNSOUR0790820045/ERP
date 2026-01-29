<?php

namespace App\Filament\Resources\TenderSourceResource\Pages;

use App\Filament\Resources\TenderSourceResource;
use Filament\Resources\Pages\ListRecords;

class ListTenderSources extends ListRecords
{
    protected static string $resource = TenderSourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
