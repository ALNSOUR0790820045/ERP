<?php

namespace App\Filament\Resources\ChequeIssuedResource\Pages;

use App\Filament\Resources\ChequeIssuedResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditChequeIssued extends EditRecord
{
    protected static string $resource = ChequeIssuedResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn ($record) => $record->status === 'draft'),
        ];
    }
}
