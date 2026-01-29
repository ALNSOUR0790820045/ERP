<?php

namespace App\Filament\Resources\TenderPurchaseApprovalResource\Pages;

use App\Filament\Resources\TenderPurchaseApprovalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTenderPurchaseApproval extends EditRecord
{
    protected static string $resource = TenderPurchaseApprovalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
