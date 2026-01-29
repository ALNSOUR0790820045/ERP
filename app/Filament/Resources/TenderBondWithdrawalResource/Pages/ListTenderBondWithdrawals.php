<?php

namespace App\Filament\Resources\TenderBondWithdrawalResource\Pages;

use App\Filament\Resources\TenderBondWithdrawalResource;
use Filament\Resources\Pages\ListRecords;

class ListTenderBondWithdrawals extends ListRecords
{
    protected static string $resource = TenderBondWithdrawalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
