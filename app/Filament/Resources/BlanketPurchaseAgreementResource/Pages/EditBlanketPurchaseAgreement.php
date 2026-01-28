<?php

namespace App\Filament\Resources\BlanketPurchaseAgreementResource\Pages;

use App\Filament\Resources\BlanketPurchaseAgreementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBlanketPurchaseAgreement extends EditRecord
{
    protected static string $resource = BlanketPurchaseAgreementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
