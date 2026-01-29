<?php

namespace App\Filament\Resources\TenderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class StageLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'stageLogs';

    protected static ?string $title = 'سجل المراحل';
    
    protected static ?string $modelLabel = 'مرحلة';
    
    protected static ?string $pluralModelLabel = 'سجل المراحل';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('stage')
                    ->label('المرحلة')
                    ->options([
                        'discovery' => 'الرصد والتسجيل',
                        'evaluation' => 'الدراسة والقرار',
                        'preparation' => 'إعداد العرض',
                        'submission' => 'التقديم',
                        'opening' => 'الفتح والنتائج',
                        'award' => 'الترسية والتحويل',
                    ])
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label('الحالة')
                    ->options([
                        'pending' => 'قيد الانتظار',
                        'in_progress' => 'جاري العمل',
                        'completed' => 'مكتمل',
                        'skipped' => 'تم تخطيه',
                    ])
                    ->default('pending'),
                Forms\Components\DateTimePicker::make('started_at')
                    ->label('تاريخ البدء'),
                Forms\Components\DateTimePicker::make('completed_at')
                    ->label('تاريخ الإكمال'),
                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('stage')
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('stage')
                    ->label('المرحلة')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'discovery' => 'الرصد',
                        'evaluation' => 'الدراسة',
                        'preparation' => 'الإعداد',
                        'submission' => 'التقديم',
                        'opening' => 'الفتح',
                        'award' => 'الترسية',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pending' => 'قيد الانتظار',
                        'in_progress' => 'جاري العمل',
                        'completed' => 'مكتمل',
                        'skipped' => 'تم تخطيه',
                        default => $state,
                    })
                    ->color(fn ($state) => match($state) {
                        'pending' => 'gray',
                        'in_progress' => 'warning',
                        'completed' => 'success',
                        'skipped' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('started_at')
                    ->label('البدء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('completed_at')
                    ->label('الإكمال')
                    ->dateTime('Y-m-d H:i'),
                Tables\Columns\TextColumn::make('completedBy.name')
                    ->label('بواسطة'),
            ])
            ->filters([])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([]);
    }
}
