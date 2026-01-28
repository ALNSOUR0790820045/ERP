<?php

namespace App\Filament\Resources\EarnedValueResource\Pages;

use App\Filament\Resources\EarnedValueResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEarnedValues extends ListRecords
{
    protected static string $resource = EarnedValueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
