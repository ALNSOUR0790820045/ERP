<?php

namespace App\Filament\Resources\FixedAssetCategoryResource\Pages;

use App\Filament\Resources\FixedAssetCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFixedAssetCategories extends ListRecords
{
    protected static string $resource = FixedAssetCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
