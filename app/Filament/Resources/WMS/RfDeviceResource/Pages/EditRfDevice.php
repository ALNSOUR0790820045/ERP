<?php

namespace App\Filament\Resources\WMS\RfDeviceResource\Pages;

use App\Filament\Resources\WMS\RfDeviceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRfDevice extends EditRecord
{
    protected static string $resource = RfDeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
