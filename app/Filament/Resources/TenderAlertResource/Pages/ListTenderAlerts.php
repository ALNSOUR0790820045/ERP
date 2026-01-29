<?php

namespace App\Filament\Resources\TenderAlertResource\Pages;

use App\Filament\Resources\TenderAlertResource;
use Filament\Resources\Pages\ListRecords;

class ListTenderAlerts extends ListRecords
{
    protected static string $resource = TenderAlertResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
