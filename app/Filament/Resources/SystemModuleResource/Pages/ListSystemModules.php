<?php

namespace App\Filament\Resources\SystemModuleResource\Pages;

use App\Filament\Resources\SystemModuleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSystemModules extends ListRecords
{
    protected static string $resource = SystemModuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('sync')
                ->label('مزامنة تلقائية')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->action(function () {
                    \Artisan::call('erp:sync-resources');
                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('تم مزامنة الشاشات')
                        ->body('تم مسح كل Filament Resources وتسجيلها تلقائياً')
                        ->send();
                }),
            Actions\CreateAction::make()
                ->label('إضافة وحدة يدوياً'),
        ];
    }
}
