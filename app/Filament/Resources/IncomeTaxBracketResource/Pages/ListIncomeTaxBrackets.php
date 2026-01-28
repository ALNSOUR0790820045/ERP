<?php

namespace App\Filament\Resources\IncomeTaxBracketResource\Pages;

use App\Filament\Resources\IncomeTaxBracketResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListIncomeTaxBrackets extends ListRecords
{
    protected static string $resource = IncomeTaxBracketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
