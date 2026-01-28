<?php

namespace App\Filament\Resources\FixedAssetCategoryResource\Pages;

use App\Filament\Resources\FixedAssetCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFixedAssetCategory extends EditRecord
{
    protected static string $resource = FixedAssetCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
