<?php

namespace App\Filament\Resources\SocialSecuritySettingResource\Pages;

use App\Filament\Resources\SocialSecuritySettingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSocialSecuritySettings extends ListRecords
{
    protected static string $resource = SocialSecuritySettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
