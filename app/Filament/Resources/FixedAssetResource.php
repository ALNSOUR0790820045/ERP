<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FixedAssetResource\Pages;
use App\Models\FixedAsset;
use App\Models\FixedAssetCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FixedAssetResource extends Resource
{
    protected static ?string $model = FixedAsset::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationGroup = 'المالية والمحاسبة';
    protected static ?string $modelLabel = 'أصل ثابت';
    protected static ?string $pluralModelLabel = 'الأصول الثابتة';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الأصل')
                    ->schema([
                        Forms\Components\TextInput::make('asset_code')
                            ->label('رمز الأصل')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50),
                        Forms\Components\TextInput::make('name')
                            ->label('اسم الأصل')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('fixed_asset_category_id')
                            ->label('التصنيف')
                            ->relationship('category', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Textarea::make('description')
                            ->label('الوصف')
                            ->rows(2),
                    ])->columns(2),

                Forms\Components\Section::make('معلومات الشراء')
                    ->schema([
                        Forms\Components\DatePicker::make('acquisition_date')
                            ->label('تاريخ الشراء')
                            ->required(),
                        Forms\Components\TextInput::make('acquisition_cost')
                            ->label('تكلفة الشراء')
                            ->numeric()
                            ->required()
                            ->prefix('JOD'),
                        Forms\Components\TextInput::make('serial_number')
                            ->label('الرقم التسلسلي')
                            ->maxLength(100),
                        Forms\Components\Select::make('supplier_id')
                            ->label('المورد')
                            ->relationship('supplier', 'name')
                            ->searchable(),
                        Forms\Components\TextInput::make('purchase_order_number')
                            ->label('رقم أمر الشراء'),
                        Forms\Components\TextInput::make('invoice_number')
                            ->label('رقم الفاتورة'),
                    ])->columns(2),

                Forms\Components\Section::make('الإهلاك')
                    ->schema([
                        Forms\Components\Select::make('depreciation_method')
                            ->label('طريقة الإهلاك')
                            ->options(FixedAsset::DEPRECIATION_METHODS)
                            ->default('straight_line')
                            ->required(),
                        Forms\Components\TextInput::make('useful_life_years')
                            ->label('العمر الإنتاجي (سنوات)')
                            ->numeric()
                            ->required()
                            ->minValue(1),
                        Forms\Components\TextInput::make('salvage_value')
                            ->label('القيمة التخريدية')
                            ->numeric()
                            ->default(0)
                            ->prefix('JOD'),
                        Forms\Components\TextInput::make('accumulated_depreciation')
                            ->label('الإهلاك المتراكم')
                            ->numeric()
                            ->disabled()
                            ->prefix('JOD'),
                        Forms\Components\TextInput::make('book_value')
                            ->label('القيمة الدفترية')
                            ->numeric()
                            ->disabled()
                            ->prefix('JOD'),
                    ])->columns(3),

                Forms\Components\Section::make('الموقع والحالة')
                    ->schema([
                        Forms\Components\TextInput::make('location')
                            ->label('الموقع')
                            ->maxLength(255),
                        Forms\Components\Select::make('project_id')
                            ->label('المشروع')
                            ->relationship('project', 'name')
                            ->searchable(),
                        Forms\Components\Select::make('department_id')
                            ->label('القسم')
                            ->relationship('department', 'name')
                            ->searchable(),
                        Forms\Components\Select::make('employee_id')
                            ->label('المسؤول')
                            ->relationship('employee', 'name')
                            ->searchable(),
                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options(FixedAsset::STATUSES)
                            ->default('active')
                            ->required(),
                        Forms\Components\DatePicker::make('disposal_date')
                            ->label('تاريخ الاستبعاد')
                            ->visible(fn ($get) => in_array($get('status'), ['disposed', 'sold'])),
                        Forms\Components\TextInput::make('disposal_amount')
                            ->label('مبلغ الاستبعاد')
                            ->numeric()
                            ->prefix('JOD')
                            ->visible(fn ($get) => in_array($get('status'), ['disposed', 'sold'])),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('asset_code')
                    ->label('الرمز')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('التصنيف')
                    ->sortable(),
                Tables\Columns\TextColumn::make('acquisition_cost')
                    ->label('التكلفة')
                    ->money('JOD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('book_value')
                    ->label('القيمة الدفترية')
                    ->money('JOD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('location')
                    ->label('الموقع')
                    ->toggleable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'under_maintenance',
                        'danger' => 'disposed',
                    ])
                    ->formatStateUsing(fn ($state) => FixedAsset::STATUSES[$state] ?? $state),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('fixed_asset_category_id')
                    ->label('التصنيف')
                    ->relationship('category', 'name'),
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(FixedAsset::STATUSES),
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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFixedAssets::route('/'),
            'create' => Pages\CreateFixedAsset::route('/create'),
            'edit' => Pages\EditFixedAsset::route('/{record}/edit'),
        ];
    }
}
