<?php

namespace App\Filament\Resources\SystemModuleResource\Pages;

use App\Filament\Resources\SystemModuleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSystemModule extends EditRecord
{
    protected static string $resource = SystemModuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function ($record) {
                    $record->screens()->delete();
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
