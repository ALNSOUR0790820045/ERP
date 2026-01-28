<?php

namespace App\Filament\Resources\TenderBondResource\Pages;

use App\Filament\Resources\TenderBondResource;
use Filament\Resources\Pages\ListRecords;

class ListTenderBonds extends ListRecords
{
    protected static string $resource = TenderBondResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
