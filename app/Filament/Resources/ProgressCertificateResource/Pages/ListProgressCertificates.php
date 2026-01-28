<?php

namespace App\Filament\Resources\ProgressCertificateResource\Pages;

use App\Filament\Resources\ProgressCertificateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProgressCertificates extends ListRecords
{
    protected static string $resource = ProgressCertificateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
