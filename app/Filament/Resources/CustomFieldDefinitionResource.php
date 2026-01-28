<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomFieldDefinitionResource\Pages;
use App\Models\CustomFieldDefinition;
use App\Models\CustomFieldGroup;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CustomFieldDefinitionResource extends Resource
{
    protected static ?string $model = CustomFieldDefinition::class;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationGroup = 'الإعدادات';

    protected static ?string $navigationLabel = 'الحقول المخصصة';

    protected static ?string $modelLabel = 'حقل مخصص';

    protected static ?string $pluralModelLabel = 'الحقول المخصصة';

    protected static ?int $navigationSort = 95;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('البيانات الأساسية')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('field_name')
                                    ->label('اسم الحقل (النظام)')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->alphaDash()
                                    ->maxLength(100),

                                Forms\Components\TextInput::make('field_label_ar')
                                    ->label('التسمية (عربي)')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('field_label_en')
                                    ->label('التسمية (إنجليزي)')
                                    ->maxLength(255),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('entity_type')
                                    ->label('الكيان')
                                    ->options(CustomFieldDefinition::ENTITY_TYPES)
                                    ->required()
                                    ->searchable(),

                                Forms\Components\Select::make('field_type')
                                    ->label('نوع الحقل')
                                    ->options(CustomFieldDefinition::FIELD_TYPES)
                                    ->required()
                                    ->live()
                                    ->native(false),
                            ]),
                    ]),

                Forms\Components\Section::make('خيارات القائمة')
                    ->visible(fn ($get) => in_array($get('field_type'), ['select', 'multiselect', 'radio', 'checkbox']))
                    ->schema([
                        Forms\Components\KeyValue::make('options')
                            ->label('الخيارات')
                            ->keyLabel('المفتاح')
                            ->valueLabel('القيمة')
                            ->addActionLabel('إضافة خيار')
                            ->reorderable(),
                    ]),

                Forms\Components\Section::make('الإعدادات')
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\Toggle::make('is_required')
                                    ->label('مطلوب')
                                    ->default(false),

                                Forms\Components\Toggle::make('is_searchable')
                                    ->label('قابل للبحث')
                                    ->default(false),

                                Forms\Components\Toggle::make('is_filterable')
                                    ->label('قابل للفلترة')
                                    ->default(false),

                                Forms\Components\Toggle::make('is_active')
                                    ->label('مفعّل')
                                    ->default(true),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('default_value')
                                    ->label('القيمة الافتراضية')
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('display_order')
                                    ->label('ترتيب العرض')
                                    ->numeric()
                                    ->default(0),
                            ]),

                        Forms\Components\KeyValue::make('validation_rules')
                            ->label('قواعد التحقق')
                            ->keyLabel('القاعدة')
                            ->valueLabel('القيمة')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('field_name')
                    ->label('اسم الحقل')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('field_label_ar')
                    ->label('التسمية (عربي)')
                    ->searchable(),

                Tables\Columns\TextColumn::make('entity_type')
                    ->label('الكيان')
                    ->formatStateUsing(fn ($state) => CustomFieldDefinition::ENTITY_TYPES[$state] ?? $state)
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('field_type')
                    ->label('نوع الحقل')
                    ->formatStateUsing(fn ($state) => CustomFieldDefinition::FIELD_TYPES[$state] ?? $state)
                    ->badge()
                    ->color('info'),

                Tables\Columns\IconColumn::make('is_required')
                    ->label('مطلوب')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_searchable')
                    ->label('قابل للبحث')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('مفعّل')
                    ->boolean(),

                Tables\Columns\TextColumn::make('display_order')
                    ->label('الترتيب')
                    ->sortable(),
            ])
            ->defaultSort('display_order')
            ->reorderable('display_order')
            ->filters([
                Tables\Filters\SelectFilter::make('entity_type')
                    ->label('الكيان')
                    ->options(CustomFieldDefinition::ENTITY_TYPES),

                Tables\Filters\SelectFilter::make('field_type')
                    ->label('نوع الحقل')
                    ->options(CustomFieldDefinition::FIELD_TYPES),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('مفعّل'),

                Tables\Filters\TernaryFilter::make('is_required')
                    ->label('مطلوب'),
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomFieldDefinitions::route('/'),
            'create' => Pages\CreateCustomFieldDefinition::route('/create'),
            'edit' => Pages\EditCustomFieldDefinition::route('/{record}/edit'),
        ];
    }
}
