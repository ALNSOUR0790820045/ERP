<?php

namespace App\Filament\Resources\SocialSecuritySettingResource\Pages;

use App\Filament\Resources\SocialSecuritySettingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSocialSecuritySetting extends CreateRecord
{
    protected static string $resource = SocialSecuritySettingResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['total_rate'] = ($data['employer_rate'] ?? 0) + ($data['employee_rate'] ?? 0);
        return $data;
    }
}
