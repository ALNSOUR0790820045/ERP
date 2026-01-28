<?php

namespace App\Filament\Resources\ChequeBookResource\Pages;

use App\Filament\Resources\ChequeBookResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditChequeBook extends EditRecord
{
    protected static string $resource = ChequeBookResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
