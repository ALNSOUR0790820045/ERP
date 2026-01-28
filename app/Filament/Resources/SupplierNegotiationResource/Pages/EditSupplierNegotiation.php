<?php

namespace App\Filament\Resources\SupplierNegotiationResource\Pages;

use App\Filament\Resources\SupplierNegotiationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSupplierNegotiation extends EditRecord
{
    protected static string $resource = SupplierNegotiationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
