<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FixedAssetCategoryResource\Pages;
use App\Models\FixedAssetCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FixedAssetCategoryResource extends Resource
{
    protected static ?string $model = FixedAssetCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';
    protected static ?string $navigationGroup = 'الأصول الثابتة';
    protected static ?string $modelLabel = 'تصنيف أصول';
    protected static ?string $pluralModelLabel = 'تصنيفات الأصول';
    protected static ?int $navigationSort = 0;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات التصنيف')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('الرمز')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(20),
                        Forms\Components\TextInput::make('name')
                            ->label('الاسم')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('parent_id')
                            ->label('التصنيف الأب')
                            ->relationship('parent', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Textarea::make('description')
                            ->label('الوصف')
                            ->rows(2),
                    ])->columns(2),

                Forms\Components\Section::make('إعدادات الإهلاك')
                    ->schema([
                        Forms\Components\Select::make('depreciation_method')
                            ->label('طريقة الإهلاك الافتراضية')
                            ->options([
                                'straight_line' => 'القسط الثابت',
                                'declining_balance' => 'القسط المتناقص',
                                'units_of_production' => 'وحدات الإنتاج',
                            ])
                            ->default('straight_line'),
                        Forms\Components\TextInput::make('default_useful_life')
                            ->label('العمر الإنتاجي الافتراضي (سنوات)')
                            ->numeric()
                            ->minValue(1),
                    ])->columns(2),

                Forms\Components\Section::make('الحسابات المالية')
                    ->schema([
                        Forms\Components\Select::make('asset_account_id')
                            ->label('حساب الأصل')
                            ->relationship('assetAccount', 'name')
                            ->searchable(),
                        Forms\Components\Select::make('depreciation_account_id')
                            ->label('حساب الإهلاك')
                            ->relationship('depreciationAccount', 'name')
                            ->searchable(),
                        Forms\Components\Select::make('expense_account_id')
                            ->label('حساب مصروف الإهلاك')
                            ->relationship('expenseAccount', 'name')
                            ->searchable(),
                    ])->columns(3),

                Forms\Components\Toggle::make('is_active')
                    ->label('فعّال')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('الرمز')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable(),
                Tables\Columns\TextColumn::make('parent.name')
                    ->label('التصنيف الأب')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('depreciation_method')
                    ->label('طريقة الإهلاك')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'straight_line' => 'القسط الثابت',
                        'declining_balance' => 'القسط المتناقص',
                        'units_of_production' => 'وحدات الإنتاج',
                        default => $state
                    }),
                Tables\Columns\TextColumn::make('default_useful_life')
                    ->label('العمر الافتراضي')
                    ->suffix(' سنة'),
                Tables\Columns\TextColumn::make('assets_count')
                    ->label('عدد الأصول')
                    ->counts('assets'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('فعّال')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('الحالة'),
            ])
            ->actions([
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
            'index' => Pages\ListFixedAssetCategories::route('/'),
            'create' => Pages\CreateFixedAssetCategory::route('/create'),
            'edit' => Pages\EditFixedAssetCategory::route('/{record}/edit'),
        ];
    }
}
