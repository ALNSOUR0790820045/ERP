<?php

namespace App\Filament\Resources\ContractAdvancePaymentResource\Pages;

use App\Filament\Resources\ContractAdvancePaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListContractAdvancePayments extends ListRecords
{
    protected static string $resource = ContractAdvancePaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
