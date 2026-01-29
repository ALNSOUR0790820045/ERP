<?php

namespace App\Filament\Resources\WMS\PrinterResource\Pages;

use App\Filament\Resources\WMS\PrinterResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePrinter extends CreateRecord
{
    protected static string $resource = PrinterResource::class;
}
