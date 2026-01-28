<?php

namespace App\Filament\Resources\ExtensionOfTimeResource\Pages;

use App\Filament\Resources\ExtensionOfTimeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExtensionOfTimes extends ListRecords
{
    protected static string $resource = ExtensionOfTimeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
