<?php

namespace App\Filament\Resources\TenderResource\Pages;

use App\Filament\Resources\TenderResource;
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
                ->visible(function () {
                    $user = auth()->user();
                    return $user->isSuperAdmin() || $user->hasPermission('tenders.tender.create');
                })
                ->url(fn () => TenderResource::getUrl('create')),
        ];
    }
}
