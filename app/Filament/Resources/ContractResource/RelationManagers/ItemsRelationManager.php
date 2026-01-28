<?php

namespace App\Filament\Resources\ContractResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';
    
    protected static ?string $title = 'بنود العقد';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('item_number')
                    ->label('رقم البند')
                    ->required()
                    ->maxLength(50),
                    
                Forms\Components\Textarea::make('description')
                    ->label('الوصف')
                    ->required()
                    ->rows(2)
                    ->columnSpanFull(),
                    
                Forms\Components\Select::make('unit_id')
                    ->label('الوحدة')
                    ->relationship('unit', 'name_ar')
                    ->searchable()
                    ->preload(),
                    
                Forms\Components\Select::make('item_type')
                    ->label('نوع البند')
                    ->options([
                        'unit_rate' => 'سعر الوحدة',
                        'lump_sum' => 'مقطوعة',
                        'provisional' => 'مؤقت',
                        'daywork' => 'عمالة يومية',
                        'contingency' => 'طوارئ',
                    ])
                    ->default('unit_rate'),
                    
                Forms\Components\TextInput::make('contract_qty')
                    ->label('الكمية التعاقدية')
                    ->numeric()
                    ->default(0),
                    
                Forms\Components\TextInput::make('unit_rate')
                    ->label('سعر الوحدة')
                    ->numeric()
                    ->default(0),
                    
                Forms\Components\TextInput::make('total_amount')
                    ->label('المبلغ الإجمالي')
                    ->numeric()
                    ->default(0),
                    
                Forms\Components\TextInput::make('sort_order')
                    ->label('الترتيب')
                    ->numeric()
                    ->default(0),
                    
                Forms\Components\Toggle::make('is_header')
                    ->label('عنوان رئيسي'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('item_number')
                    ->label('رقم البند')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('description')
                    ->label('الوصف')
                    ->limit(50)
                    ->wrap(),
                    
                Tables\Columns\TextColumn::make('unit.name_ar')
                    ->label('الوحدة'),
                    
                Tables\Columns\TextColumn::make('contract_qty')
                    ->label('الكمية')
                    ->numeric(decimalPlaces: 2),
                    
                Tables\Columns\TextColumn::make('unit_rate')
                    ->label('سعر الوحدة')
                    ->money('JOD'),
                    
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('الإجمالي')
                    ->money('JOD'),
                    
                Tables\Columns\TextColumn::make('executed_qty')
                    ->label('المنفذ')
                    ->numeric(decimalPlaces: 2),
                    
                Tables\Columns\TextColumn::make('execution_percentage')
                    ->label('نسبة الإنجاز')
                    ->suffix('%')
                    ->state(fn ($record) => $record->execution_percentage),
            ])
            ->filters([
                //
            ])
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
            ])
            ->defaultSort('sort_order');
    }
}
