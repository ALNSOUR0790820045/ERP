<?php

namespace App\Filament\Resources\TenderBondWithdrawalResource\Pages;

use App\Filament\Resources\TenderBondWithdrawalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTenderBondWithdrawal extends EditRecord
{
    protected static string $resource = TenderBondWithdrawalResource::class;

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
