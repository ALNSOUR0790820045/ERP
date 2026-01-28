<?php

namespace App\Filament\Resources\ChequeReceivedResource\Pages;

use App\Filament\Resources\ChequeReceivedResource;
use Filament\Resources\Pages\ListRecords;

class ListChequesReceived extends ListRecords
{
    protected static string $resource = ChequeReceivedResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
