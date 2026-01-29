<?php

namespace App\Filament\Resources\WMS\BarcodeFormatResource\Pages;

use App\Filament\Resources\WMS\BarcodeFormatResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBarcodeFormats extends ListRecords
{
    protected static string $resource = BarcodeFormatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
