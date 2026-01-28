<?php

namespace App\Filament\Resources\BlanketPurchaseAgreementResource\Pages;

use App\Filament\Resources\BlanketPurchaseAgreementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBlanketPurchaseAgreements extends ListRecords
{
    protected static string $resource = BlanketPurchaseAgreementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
