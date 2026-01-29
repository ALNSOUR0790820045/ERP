<?php

namespace App\Filament\Resources\TenderBondRenewalResource\Pages;

use App\Filament\Resources\TenderBondRenewalResource;
use Filament\Resources\Pages\ListRecords;

class ListTenderBondRenewals extends ListRecords
{
    protected static string $resource = TenderBondRenewalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
