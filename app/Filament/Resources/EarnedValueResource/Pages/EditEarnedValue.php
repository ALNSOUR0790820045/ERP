<?php

namespace App\Filament\Resources\EarnedValueResource\Pages;

use App\Filament\Resources\EarnedValueResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEarnedValue extends EditRecord
{
    protected static string $resource = EarnedValueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
