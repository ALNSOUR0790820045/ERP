<?php

namespace App\Filament\Resources\TenderPurchaseApprovalResource\Pages;

use App\Filament\Resources\TenderPurchaseApprovalResource;
use Filament\Resources\Pages\ListRecords;

class ListTenderPurchaseApprovals extends ListRecords
{
    protected static string $resource = TenderPurchaseApprovalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
