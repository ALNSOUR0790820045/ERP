<?php

namespace App\Filament\Widgets;

use App\Models\Tender;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class UpcomingTenderDeadlinesWidget extends BaseWidget
{
    protected static ?string $heading = 'مواعيد الإغلاق القريبة';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Tender::query()
                    ->where('submission_deadline', '>=', now())
                    ->where('submission_deadline', '<=', now()->addDays(14))
                    ->whereIn('status', ['active', 'in_progress', 'pending', 'draft'])
                    ->orderBy('submission_deadline')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name_ar')
                    ->label('العطاء')
                    ->limit(40)
                    ->searchable(),
                Tables\Columns\TextColumn::make('organization.name_ar')
                    ->label('الجهة')
                    ->limit(25),
                Tables\Columns\TextColumn::make('submission_deadline')
                    ->label('موعد الإغلاق')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->color(fn ($record) => 
                        $record->submission_deadline <= now()->addDays(3) ? 'danger' : 
                        ($record->submission_deadline <= now()->addDays(7) ? 'warning' : 'success')
                    ),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn($state) => match($state) {
                        'draft' => 'مسودة',
                        'pending' => 'معلق',
                        'active' => 'نشط',
                        'in_progress' => 'قيد التجهيز',
                        default => $state,
                    })
                    ->color(fn($state) => match($state) {
                        'active', 'in_progress' => 'success',
                        'pending' => 'warning',
                        'draft' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('days_remaining')
                    ->label('الأيام المتبقية')
                    ->getStateUsing(fn($record) => 
                        now()->diffInDays($record->submission_deadline, false)
                    )
                    ->suffix(' يوم')
                    ->color(fn ($state) => 
                        $state <= 3 ? 'danger' : 
                        ($state <= 7 ? 'warning' : 'success')
                    ),
            ])
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5)
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('عرض')
                    ->url(fn ($record) => route('filament.admin.resources.tenders.edit', $record))
                    ->icon('heroicon-o-eye'),
            ]);
    }
}
