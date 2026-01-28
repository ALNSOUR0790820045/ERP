<?php

namespace App\Filament\Resources\ChequeReceivedResource\Pages;

use App\Filament\Resources\ChequeReceivedResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditChequeReceived extends EditRecord
{
    protected static string $resource = ChequeReceivedResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn ($record) => $record->status === 'received'),
        ];
    }
}
