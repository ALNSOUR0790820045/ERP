<?php

namespace App\Filament\Resources\SocialSecuritySettingResource\Pages;

use App\Filament\Resources\SocialSecuritySettingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSocialSecuritySetting extends EditRecord
{
    protected static string $resource = SocialSecuritySettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['total_rate'] = ($data['employer_rate'] ?? 0) + ($data['employee_rate'] ?? 0);
        return $data;
    }
}
