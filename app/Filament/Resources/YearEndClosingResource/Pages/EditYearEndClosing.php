<?php

namespace App\Filament\Resources\YearEndClosingResource\Pages;

use App\Filament\Resources\YearEndClosingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditYearEndClosing extends EditRecord
{
    protected static string $resource = YearEndClosingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
