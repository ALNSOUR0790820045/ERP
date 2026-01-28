<?php

namespace App\Filament\Resources\BimModelResource\Pages;

use App\Filament\Resources\BimModelResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;

class ViewBimModel extends ViewRecord
{
    protected static string $resource = BimModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
