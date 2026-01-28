<?php

namespace App\Filament\Resources\FinalAccountResource\Pages;

use App\Filament\Resources\FinalAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFinalAccount extends EditRecord
{
    protected static string $resource = FinalAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
