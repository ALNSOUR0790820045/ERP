<?php

namespace App\Filament\Resources\TenderClarificationResource\Pages;

use App\Filament\Resources\TenderClarificationResource;
use Filament\Resources\Pages\ListRecords;

class ListTenderClarifications extends ListRecords
{
    protected static string $resource = TenderClarificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
