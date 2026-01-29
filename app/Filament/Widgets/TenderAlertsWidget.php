<?php

namespace App\Filament\Widgets;

use App\Models\Tenders\TenderAlert;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TenderAlertsWidget extends BaseWidget
{
    protected static ?string $heading = 'تنبيهات العطاءات';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                TenderAlert::query()
                    ->where('is_active', true)
                    ->whereNull('resolved_at')
                    ->orderByRaw("CASE 
                        WHEN priority = 'urgent' THEN 1 
                        WHEN priority = 'high' THEN 2 
                        WHEN priority = 'medium' THEN 3 
                        ELSE 4 
                    END")
                    ->orderBy('alert_date', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('tender.name_ar')
                    ->label('العطاء')
                    ->limit(30),
                Tables\Columns\TextColumn::make('alert_type')
                    ->label('نوع التنبيه')
                    ->badge()
                    ->formatStateUsing(fn($state) => TenderAlert::ALERT_TYPES[$state] ?? $state)
                    ->color('info'),
                Tables\Columns\TextColumn::make('priority')
                    ->label('الأولوية')
                    ->badge()
                    ->formatStateUsing(fn($state) => TenderAlert::PRIORITIES[$state] ?? $state)
                    ->color(fn($state) => match($state) {
                        'urgent' => 'danger',
                        'high' => 'warning',
                        'medium' => 'info',
                        'low' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('alert_date')
                    ->label('التاريخ')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('message')
                    ->label('الرسالة')
                    ->limit(50),
            ])
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5)
            ->actions([
                Tables\Actions\Action::make('resolve')
                    ->label('حل')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(fn ($record) => $record->resolve(auth()->user())),
            ]);
    }
}
