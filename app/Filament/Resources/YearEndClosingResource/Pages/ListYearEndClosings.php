<?php

namespace App\Filament\Resources\YearEndClosingResource\Pages;

use App\Filament\Resources\YearEndClosingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListYearEndClosings extends ListRecords
{
    protected static string $resource = YearEndClosingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
