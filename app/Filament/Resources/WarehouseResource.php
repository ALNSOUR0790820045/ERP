<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WarehouseResource\Pages;
use App\Models\Warehouse;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WarehouseResource extends Resource
{
    protected static ?string $model = Warehouse::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationGroup = 'المشتريات والمخازن';
    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return 'مستودع';
    }

    public static function getPluralModelLabel(): string
    {
        return 'المستودعات';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('بيانات المستودع')->schema([
                Forms\Components\TextInput::make('code')
                    ->label('الكود')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(20),
                Forms\Components\TextInput::make('name_ar')
                    ->label('الاسم بالعربية')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('name_en')
                    ->label('الاسم بالإنجليزية')
                    ->maxLength(255),
                Forms\Components\Select::make('warehouse_type')
                    ->label('نوع المستودع')
                    ->options([
                        'general' => 'عام',
                        'project' => 'مشروع',
                        'main' => 'رئيسي',
                        'transit' => 'عبور',
                    ])
                    ->default('general'),
                Forms\Components\Select::make('company_id')
                    ->label('الشركة')
                    ->relationship('company', 'name_ar'),
                Forms\Components\Select::make('branch_id')
                    ->label('الفرع')
                    ->relationship('branch', 'name_ar'),
                Forms\Components\Select::make('project_id')
                    ->label('المشروع')
                    ->relationship('project', 'name_ar'),
            ])->columns(2),

            Forms\Components\Section::make('معلومات الاتصال')->schema([
                Forms\Components\Textarea::make('address')
                    ->label('العنوان')
                    ->rows(2),
                Forms\Components\TextInput::make('manager_name')
                    ->label('مدير المستودع')
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->label('الهاتف')
                    ->tel()
                    ->maxLength(50),
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
                Tables\Columns\TextColumn::make('code')->label('الكود')->searchable(),
                Tables\Columns\TextColumn::make('name_ar')->label('الاسم')->searchable(),
                Tables\Columns\TextColumn::make('warehouse_type')->label('النوع')
                    ->badge(),
                Tables\Columns\TextColumn::make('company.name_ar')->label('الشركة'),
                Tables\Columns\TextColumn::make('manager_name')->label('المدير'),
                Tables\Columns\IconColumn::make('is_active')->label('نشط')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('warehouse_type')
                    ->label('النوع')
                    ->options([
                        'general' => 'عام',
                        'project' => 'مشروع',
                        'main' => 'رئيسي',
                    ]),
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
            'index' => Pages\ListWarehouses::route('/'),
            'create' => Pages\CreateWarehouse::route('/create'),
            'edit' => Pages\EditWarehouse::route('/{record}/edit'),
        ];
    }
}
