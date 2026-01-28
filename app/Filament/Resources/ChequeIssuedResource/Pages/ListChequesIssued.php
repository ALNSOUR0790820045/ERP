<?php

namespace App\Filament\Resources\ChequeIssuedResource\Pages;

use App\Filament\Resources\ChequeIssuedResource;
use Filament\Resources\Pages\ListRecords;

class ListChequesIssued extends ListRecords
{
    protected static string $resource = ChequeIssuedResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
