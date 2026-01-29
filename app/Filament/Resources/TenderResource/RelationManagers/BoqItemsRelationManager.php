<?php

namespace App\Filament\Resources\TenderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class BoqItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'boqItems';

    protected static ?string $title = 'جدول الكميات';
    
    protected static ?string $modelLabel = 'بند';
    
    protected static ?string $pluralModelLabel = 'بنود جدول الكميات';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('item_number')
                    ->label('رقم البند')
                    ->required()
                    ->maxLength(50),
                Forms\Components\TextInput::make('description')
                    ->label('الوصف')
                    ->required()
                    ->maxLength(500)
                    ->columnSpanFull(),
                Forms\Components\Select::make('unit_id')
                    ->label('الوحدة')
                    ->relationship('unit', 'name_ar')
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('quantity')
                    ->label('الكمية')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('unit_price')
                    ->label('سعر الوحدة')
                    ->numeric()
                    ->prefix('د.أ'),
                Forms\Components\TextInput::make('total_price')
                    ->label('الإجمالي')
                    ->numeric()
                    ->prefix('د.أ')
                    ->disabled()
                    ->dehydrated(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('item_number')
            ->columns([
                Tables\Columns\TextColumn::make('item_number')
                    ->label('رقم البند')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('الوصف')
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\TextColumn::make('unit.name_ar')
                    ->label('الوحدة'),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('الكمية')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit_price')
                    ->label('سعر الوحدة')
                    ->money('JOD'),
                Tables\Columns\TextColumn::make('total_price')
                    ->label('الإجمالي')
                    ->money('JOD')
                    ->sortable()
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('JOD'),
                    ]),
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
