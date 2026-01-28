<?php

namespace App\Filament\Resources\ContractRetentionResource\Pages;

use App\Filament\Resources\ContractRetentionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListContractRetentions extends ListRecords
{
    protected static string $resource = ContractRetentionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
