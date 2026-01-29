<?php

namespace App\Filament\Resources\TenderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SiteVisitsRelationManager extends RelationManager
{
    protected static string $relationship = 'siteVisits';

    protected static ?string $title = 'زيارات الموقع';
    
    protected static ?string $modelLabel = 'زيارة';
    
    protected static ?string $pluralModelLabel = 'زيارات الموقع';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DateTimePicker::make('visit_date')
                    ->label('تاريخ الزيارة')
                    ->required(),
                Forms\Components\TextInput::make('location')
                    ->label('الموقع')
                    ->maxLength(255),
                Forms\Components\Select::make('visit_type')
                    ->label('نوع الزيارة')
                    ->options([
                        'mandatory' => 'إلزامية',
                        'optional' => 'اختيارية',
                    ])
                    ->default('optional'),
                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('findings')
                    ->label('النتائج والملاحظات')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('attended')
                    ->label('تم الحضور'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('visit_date')
            ->columns([
                Tables\Columns\TextColumn::make('visit_date')
                    ->label('تاريخ الزيارة')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('location')
                    ->label('الموقع')
                    ->limit(30),
                Tables\Columns\TextColumn::make('visit_type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'mandatory' ? 'إلزامية' : 'اختيارية')
                    ->color(fn ($state) => $state === 'mandatory' ? 'danger' : 'gray'),
                Tables\Columns\IconColumn::make('attended')
                    ->label('تم الحضور')
                    ->boolean(),
            ])
            ->filters([])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
