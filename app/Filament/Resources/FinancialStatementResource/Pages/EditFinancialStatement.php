<?php

namespace App\Filament\Resources\FinancialStatementResource\Pages;

use App\Filament\Resources\FinancialStatementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFinancialStatement extends EditRecord
{
    protected static string $resource = FinancialStatementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
