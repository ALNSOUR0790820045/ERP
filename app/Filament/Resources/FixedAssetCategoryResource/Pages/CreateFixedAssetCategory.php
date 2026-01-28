<?php

namespace App\Filament\Resources\FixedAssetCategoryResource\Pages;

use App\Filament\Resources\FixedAssetCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFixedAssetCategory extends CreateRecord
{
    protected static string $resource = FixedAssetCategoryResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
