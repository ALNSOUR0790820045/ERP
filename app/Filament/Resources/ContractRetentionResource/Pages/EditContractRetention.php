<?php

namespace App\Filament\Resources\ContractRetentionResource\Pages;

use App\Filament\Resources\ContractRetentionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditContractRetention extends EditRecord
{
    protected static string $resource = ContractRetentionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
