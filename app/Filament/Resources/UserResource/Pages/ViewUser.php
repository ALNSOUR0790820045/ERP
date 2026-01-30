<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('toggle_active')
                ->label(fn () => $this->record->is_active ? 'تعطيل الحساب' : 'تفعيل الحساب')
                ->icon(fn () => $this->record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                ->color(fn () => $this->record->is_active ? 'danger' : 'success')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['is_active' => !$this->record->is_active]);
                    $this->refreshFormData(['is_active']);
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('معلومات الحساب')
                    ->icon('heroicon-o-user')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\ImageEntry::make('avatar')
                            ->label('الصورة')
                            ->circular()
                            ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&background=random'),
                        Infolists\Components\TextEntry::make('name')
                            ->label('الاسم'),
                        Infolists\Components\TextEntry::make('email')
                            ->label('البريد الإلكتروني')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('username')
                            ->label('اسم الدخول'),
                        Infolists\Components\TextEntry::make('phone')
                            ->label('الهاتف'),
                        Infolists\Components\IconEntry::make('is_active')
                            ->label('الحالة')
                            ->boolean(),
                    ]),

                Infolists\Components\Section::make('الدور والصلاحيات')
                    ->icon('heroicon-o-shield-check')
                    ->columns(2)
                    ->schema([
                        Infolists\Components\TextEntry::make('role.name_ar')
                            ->label('الدور')
                            ->badge(),
                        Infolists\Components\TextEntry::make('branch.name_ar')
                            ->label('الفرع'),
                        Infolists\Components\RepeatableEntry::make('role.permissions')
                            ->label('الصلاحيات')
                            ->columnSpanFull()
                            ->columns(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('name_ar')
                                    ->label('')
                                    ->badge()
                                    ->color('success'),
                            ]),
                    ]),

                Infolists\Components\Section::make('معلومات النظام')
                    ->icon('heroicon-o-information-circle')
                    ->columns(4)
                    ->collapsed()
                    ->schema([
                        Infolists\Components\TextEntry::make('last_login_at')
                            ->label('آخر دخول')
                            ->dateTime()
                            ->since(),
                        Infolists\Components\TextEntry::make('last_login_ip')
                            ->label('IP آخر دخول'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('تاريخ الإنشاء')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('آخر تحديث')
                            ->dateTime(),
                    ]),
            ]);
    }
}
