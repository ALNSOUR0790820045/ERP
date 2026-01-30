<?php

namespace App\Filament\Resources\TenderResource\Pages;

use App\Filament\Resources\TenderResource;
use App\Filament\Pages\TenderWorkflow;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTenders extends ListRecords
{
    protected static string $resource = TenderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('new_tender')
                ->label('رصد عطاء جديد')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->url(TenderWorkflow::getUrl()),
        ];
    }
}
