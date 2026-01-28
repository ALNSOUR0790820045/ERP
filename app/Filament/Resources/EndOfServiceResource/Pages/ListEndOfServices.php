<?php

namespace App\Filament\Resources\EndOfServiceResource\Pages;

use App\Filament\Resources\EndOfServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEndOfServices extends ListRecords
{
    protected static string $resource = EndOfServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
