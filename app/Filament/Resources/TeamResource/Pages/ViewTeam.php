<?php

namespace App\Filament\Resources\TeamResource\Pages;

use App\Filament\Resources\TeamResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewTeam extends ViewRecord
{
    protected static string $resource = TeamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('معلومات الفريق')
                    ->icon('heroicon-o-user-group')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\TextEntry::make('name_ar')
                            ->label('الاسم بالعربي'),
                        Infolists\Components\TextEntry::make('name_en')
                            ->label('الاسم بالإنجليزي'),
                        Infolists\Components\TextEntry::make('code')
                            ->label('الرمز')
                            ->badge(),
                        Infolists\Components\TextEntry::make('type')
                            ->label('النوع')
                            ->badge()
                            ->formatStateUsing(fn ($state) => match($state) {
                                'general' => 'عام',
                                'tender' => 'عطاءات',
                                'project' => 'مشروع',
                                'department' => 'قسم',
                                'pricing' => 'تسعير',
                                'technical' => 'فني',
                                default => $state,
                            }),
                        Infolists\Components\TextEntry::make('leader.name')
                            ->label('القائد'),
                        Infolists\Components\TextEntry::make('branch.name_ar')
                            ->label('الفرع'),
                        Infolists\Components\TextEntry::make('description')
                            ->label('الوصف')
                            ->columnSpanFull(),
                        Infolists\Components\IconEntry::make('is_active')
                            ->label('نشط')
                            ->boolean(),
                    ]),

                Infolists\Components\Section::make('أعضاء الفريق')
                    ->icon('heroicon-o-users')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('teamMembers')
                            ->label('')
                            ->columns(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('user.name')
                                    ->label('العضو'),
                                Infolists\Components\TextEntry::make('role_in_team')
                                    ->label('الدور')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => match($state) {
                                        'leader' => 'قائد',
                                        'member' => 'عضو',
                                        'viewer' => 'مشاهد',
                                        default => $state,
                                    })
                                    ->color(fn ($state) => match($state) {
                                        'leader' => 'success',
                                        'member' => 'info',
                                        'viewer' => 'gray',
                                        default => 'gray',
                                    }),
                                Infolists\Components\TextEntry::make('joined_at')
                                    ->label('تاريخ الانضمام')
                                    ->date(),
                                Infolists\Components\IconEntry::make('is_active')
                                    ->label('نشط')
                                    ->boolean(),
                            ]),
                    ]),
            ]);
    }
}
