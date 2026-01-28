<?php

namespace App\Filament\Resources\ChequeBookResource\Pages;

use App\Filament\Resources\ChequeBookResource;
use Filament\Resources\Pages\ListRecords;

class ListChequeBooks extends ListRecords
{
    protected static string $resource = ChequeBookResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
