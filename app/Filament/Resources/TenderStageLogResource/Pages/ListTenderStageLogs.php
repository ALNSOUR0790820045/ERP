<?php

namespace App\Filament\Resources\TenderStageLogResource\Pages;

use App\Filament\Resources\TenderStageLogResource;
use Filament\Resources\Pages\ListRecords;

class ListTenderStageLogs extends ListRecords
{
    protected static string $resource = TenderStageLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
