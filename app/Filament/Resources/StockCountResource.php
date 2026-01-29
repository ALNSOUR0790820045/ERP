<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockCountResource\Pages;
use App\Models\StockCount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StockCountResource extends Resource
{
    protected static ?string $model = StockCount::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'المشتريات والمخازن';
    protected static ?string $modelLabel = 'جرد مخزون';
    protected static ?string $pluralModelLabel = 'جرد المخزون';
    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الجرد')
                    ->schema([
                        Forms\Components\TextInput::make('count_number')
                            ->label('رقم الجرد')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->default(fn () => 'CNT-' . date('Ymd') . '-' . rand(100, 999)),
                        Forms\Components\Select::make('warehouse_id')
                            ->label('المستودع')
                            ->relationship('warehouse', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\DatePicker::make('count_date')
                            ->label('تاريخ الجرد')
                            ->required()
                            ->default(now()),
                        Forms\Components\Select::make('count_type')
                            ->label('نوع الجرد')
                            ->options(StockCount::COUNT_TYPES)
                            ->required()
                            ->default('periodic'),
                    ])->columns(2),

                Forms\Components\Section::make('التفاصيل')
                    ->schema([
                        Forms\Components\Select::make('fiscal_period_id')
                            ->label('الفترة المالية')
                            ->relationship('fiscalPeriod', 'name')
                            ->searchable(),
                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options(StockCount::STATUSES)
                            ->default('draft')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('المسؤولون')
                    ->schema([
                        Forms\Components\Select::make('counted_by')
                            ->label('قام بالعد')
                            ->relationship('countedBy', 'name')
                            ->searchable(),
                        Forms\Components\Select::make('verified_by')
                            ->label('تحقق منه')
                            ->relationship('verifiedBy', 'name')
                            ->searchable(),
                        Forms\Components\Select::make('approved_by')
                            ->label('اعتمده')
                            ->relationship('approvedBy', 'name')
                            ->searchable(),
                        Forms\Components\DatePicker::make('approval_date')
                            ->label('تاريخ الاعتماد'),
                    ])->columns(2),

                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات')
                    ->rows(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('count_number')
                    ->label('رقم الجرد')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('المستودع')
                    ->sortable(),
                Tables\Columns\TextColumn::make('count_date')
                    ->label('التاريخ')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('count_type')
                    ->label('النوع')
                    ->formatStateUsing(fn ($state) => StockCount::COUNT_TYPES[$state] ?? $state),
                Tables\Columns\TextColumn::make('items_count')
                    ->label('عدد الأصناف')
                    ->counts('items'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors([
                        'gray' => 'draft',
                        'info' => 'in_progress',
                        'warning' => 'counted',
                        'primary' => 'verified',
                        'success' => 'approved',
                    ])
                    ->formatStateUsing(fn ($state) => StockCount::STATUSES[$state] ?? $state),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('warehouse_id')
                    ->label('المستودع')
                    ->relationship('warehouse', 'name'),
                Tables\Filters\SelectFilter::make('count_type')
                    ->label('النوع')
                    ->options(StockCount::COUNT_TYPES),
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(StockCount::STATUSES),
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
            'index' => Pages\ListStockCounts::route('/'),
            'create' => Pages\CreateStockCount::route('/create'),
            'edit' => Pages\EditStockCount::route('/{record}/edit'),
        ];
    }
}
