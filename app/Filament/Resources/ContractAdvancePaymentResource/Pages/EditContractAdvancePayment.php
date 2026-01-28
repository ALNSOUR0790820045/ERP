<?php

namespace App\Filament\Resources\ContractAdvancePaymentResource\Pages;

use App\Filament\Resources\ContractAdvancePaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditContractAdvancePayment extends EditRecord
{
    protected static string $resource = ContractAdvancePaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
