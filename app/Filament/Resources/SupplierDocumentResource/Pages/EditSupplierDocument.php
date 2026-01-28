<?php

namespace App\Filament\Resources\SupplierDocumentResource\Pages;

use App\Filament\Resources\SupplierDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSupplierDocument extends EditRecord
{
    protected static string $resource = SupplierDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
