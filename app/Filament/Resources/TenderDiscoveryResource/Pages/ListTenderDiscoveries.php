<?php

namespace App\Filament\Resources\TenderDiscoveryResource\Pages;

use App\Filament\Resources\TenderDiscoveryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTenderDiscoveries extends ListRecords
{
    protected static string $resource = TenderDiscoveryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
