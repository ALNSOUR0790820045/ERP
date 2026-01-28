<?php

namespace App\Filament\Resources\TenderBondResource\Pages;

use App\Filament\Resources\TenderBondResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTenderBond extends EditRecord
{
    protected static string $resource = TenderBondResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
