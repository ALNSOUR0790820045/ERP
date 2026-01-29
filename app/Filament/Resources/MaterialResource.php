<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MaterialResource\Pages;
use App\Models\Material;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MaterialResource extends Resource
{
    protected static ?string $model = Material::class;
    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationGroup = 'المشتريات والمخازن';
    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return 'مادة';
    }

    public static function getPluralModelLabel(): string
    {
        return 'المواد';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make('Tabs')->tabs([
                Forms\Components\Tabs\Tab::make('البيانات الأساسية')->schema([
                    Forms\Components\TextInput::make('code')
                        ->label('الكود')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(50),
                    Forms\Components\TextInput::make('barcode')
                        ->label('الباركود')
                        ->maxLength(50),
                    Forms\Components\TextInput::make('name_ar')
                        ->label('الاسم بالعربية')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('name_en')
                        ->label('الاسم بالإنجليزية')
                        ->maxLength(255),
                    Forms\Components\Select::make('category_id')
                        ->label('التصنيف')
                        ->relationship('category', 'name_ar'),
                    Forms\Components\Textarea::make('description')
                        ->label('الوصف')
                        ->rows(2)
                        ->columnSpan(2),
                ])->columns(2),

                Forms\Components\Tabs\Tab::make('الوحدات والتحويل')->schema([
                    Forms\Components\Select::make('unit_id')
                        ->label('وحدة المخزون')
                        ->relationship('unit', 'name_ar'),
                    Forms\Components\Select::make('purchase_unit_id')
                        ->label('وحدة الشراء')
                        ->relationship('purchaseUnit', 'name_ar'),
                    Forms\Components\TextInput::make('conversion_factor')
                        ->label('معامل التحويل')
                        ->numeric()
                        ->default(1),
                    Forms\Components\Select::make('valuation_method')
                        ->label('طريقة التقييم')
                        ->options([
                            'average' => 'المتوسط المرجح',
                            'fifo' => 'الوارد أولاً صادر أولاً',
                            'lifo' => 'الوارد أخيراً صادر أولاً',
                        ])
                        ->default('average'),
                ])->columns(2),

                Forms\Components\Tabs\Tab::make('حدود المخزون')->schema([
                    Forms\Components\TextInput::make('min_stock')
                        ->label('الحد الأدنى')
                        ->numeric()
                        ->default(0),
                    Forms\Components\TextInput::make('max_stock')
                        ->label('الحد الأقصى')
                        ->numeric(),
                    Forms\Components\TextInput::make('reorder_point')
                        ->label('نقطة إعادة الطلب')
                        ->numeric(),
                    Forms\Components\TextInput::make('reorder_qty')
                        ->label('كمية إعادة الطلب')
                        ->numeric(),
                ])->columns(2),

                Forms\Components\Tabs\Tab::make('التكاليف')->schema([
                    Forms\Components\TextInput::make('last_purchase_price')
                        ->label('آخر سعر شراء')
                        ->numeric(),
                    Forms\Components\TextInput::make('average_cost')
                        ->label('متوسط التكلفة')
                        ->numeric(),
                    Forms\Components\TextInput::make('standard_cost')
                        ->label('التكلفة المعيارية')
                        ->numeric(),
                    Forms\Components\Toggle::make('is_serialized')
                        ->label('مسلسل'),
                    Forms\Components\Toggle::make('is_active')
                        ->label('نشط')
                        ->default(true),
                ])->columns(2),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('الكود')->searchable(),
                Tables\Columns\TextColumn::make('name_ar')->label('الاسم')->searchable(),
                Tables\Columns\TextColumn::make('category.name_ar')->label('التصنيف'),
                Tables\Columns\TextColumn::make('unit.name_ar')->label('الوحدة'),
                Tables\Columns\TextColumn::make('average_cost')->label('متوسط التكلفة')
                    ->money('JOD'),
                Tables\Columns\IconColumn::make('is_active')->label('نشط')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('التصنيف')
                    ->relationship('category', 'name_ar'),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMaterials::route('/'),
            'create' => Pages\CreateMaterial::route('/create'),
            'edit' => Pages\EditMaterial::route('/{record}/edit'),
        ];
    }
}
