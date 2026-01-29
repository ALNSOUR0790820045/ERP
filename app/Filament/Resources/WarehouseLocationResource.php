<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WarehouseLocationResource\Pages;
use App\Models\WarehouseLocation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WarehouseLocationResource extends Resource
{
    protected static ?string $model = WarehouseLocation::class;
    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $navigationGroup = 'المشتريات والمخازن';
    protected static ?string $modelLabel = 'موقع تخزين';
    protected static ?string $pluralModelLabel = 'مواقع التخزين';
    protected static ?int $navigationSort = 15;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('بيانات الموقع')
                ->schema([
                    Forms\Components\Select::make('warehouse_id')
                        ->label('المستودع')
                        ->relationship('warehouse', 'name_ar')
                        ->required()
                        ->searchable()
                        ->preload(),
                    Forms\Components\TextInput::make('location_code')
                        ->label('كود الموقع')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(50),
                    Forms\Components\TextInput::make('zone')
                        ->label('المنطقة')
                        ->maxLength(20),
                    Forms\Components\TextInput::make('aisle')
                        ->label('الممر')
                        ->maxLength(20),
                    Forms\Components\TextInput::make('rack')
                        ->label('الرف')
                        ->maxLength(20),
                    Forms\Components\TextInput::make('shelf')
                        ->label('الرفة')
                        ->maxLength(20),
                    Forms\Components\TextInput::make('bin')
                        ->label('الخانة')
                        ->maxLength(20),
                    Forms\Components\TextInput::make('capacity')
                        ->label('السعة')
                        ->numeric(),
                    Forms\Components\TextInput::make('unit')
                        ->label('وحدة السعة')
                        ->maxLength(20),
                    Forms\Components\Toggle::make('is_active')
                        ->label('نشط')
                        ->default(true),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('warehouse.name_ar')
                    ->label('المستودع')
                    ->searchable(),
                Tables\Columns\TextColumn::make('location_code')
                    ->label('كود الموقع')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('zone')
                    ->label('المنطقة'),
                Tables\Columns\TextColumn::make('aisle')
                    ->label('الممر'),
                Tables\Columns\TextColumn::make('rack')
                    ->label('الرف'),
                Tables\Columns\TextColumn::make('capacity')
                    ->label('السعة')
                    ->numeric(),
                Tables\Columns\TextColumn::make('current_usage')
                    ->label('الاستخدام')
                    ->numeric(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('warehouse_id')
                    ->label('المستودع')
                    ->relationship('warehouse', 'name_ar'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('نشط'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWarehouseLocations::route('/'),
            'create' => Pages\CreateWarehouseLocation::route('/create'),
            'edit' => Pages\EditWarehouseLocation::route('/{record}/edit'),
        ];
    }
}
