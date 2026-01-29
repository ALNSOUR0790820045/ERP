<?php

namespace App\Filament\Resources\TenderAwardTrackingResource\Pages;

use App\Filament\Resources\TenderAwardTrackingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTenderAwardTrackings extends ListRecords
{
    protected static string $resource = TenderAwardTrackingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
