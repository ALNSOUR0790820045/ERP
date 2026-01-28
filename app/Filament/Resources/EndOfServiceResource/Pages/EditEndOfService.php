<?php

namespace App\Filament\Resources\EndOfServiceResource\Pages;

use App\Filament\Resources\EndOfServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEndOfService extends EditRecord
{
    protected static string $resource = EndOfServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
