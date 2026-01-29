<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockAdjustmentResource\Pages;
use App\Models\StockAdjustment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StockAdjustmentResource extends Resource
{
    protected static ?string $model = StockAdjustment::class;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';
    protected static ?string $navigationGroup = 'المشتريات والمخازن';
    protected static ?string $modelLabel = 'تسوية مخزون';
    protected static ?string $pluralModelLabel = 'تسويات المخزون';
    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات التسوية')
                    ->schema([
                        Forms\Components\TextInput::make('adjustment_number')
                            ->label('رقم التسوية')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->default(fn () => 'ADJ-' . date('Ymd') . '-' . rand(100, 999)),
                        Forms\Components\Select::make('warehouse_id')
                            ->label('المستودع')
                            ->relationship('warehouse', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\DatePicker::make('adjustment_date')
                            ->label('تاريخ التسوية')
                            ->required()
                            ->default(now()),
                        Forms\Components\Select::make('adjustment_type')
                            ->label('نوع التسوية')
                            ->options(StockAdjustment::ADJUSTMENT_TYPES)
                            ->required()
                            ->default('correction'),
                    ])->columns(2),

                Forms\Components\Section::make('المرجع')
                    ->schema([
                        Forms\Components\Select::make('stock_count_id')
                            ->label('من الجرد')
                            ->relationship('stockCount', 'count_number')
                            ->searchable()
                            ->helperText('اختر الجرد إذا كانت التسوية ناتجة عن جرد'),
                    ]),

                Forms\Components\Section::make('الملخص')
                    ->schema([
                        Forms\Components\TextInput::make('total_increase_value')
                            ->label('إجمالي الزيادة')
                            ->numeric()
                            ->default(0)
                            ->prefix('JOD')
                            ->disabled(),
                        Forms\Components\TextInput::make('total_decrease_value')
                            ->label('إجمالي النقص')
                            ->numeric()
                            ->default(0)
                            ->prefix('JOD')
                            ->disabled(),
                        Forms\Components\TextInput::make('net_adjustment_value')
                            ->label('صافي التسوية')
                            ->numeric()
                            ->default(0)
                            ->prefix('JOD')
                            ->disabled(),
                    ])->columns(3),

                Forms\Components\Section::make('الحالة')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options(StockAdjustment::STATUSES)
                            ->default('draft')
                            ->required(),
                        Forms\Components\Select::make('prepared_by')
                            ->label('أعده')
                            ->relationship('preparedBy', 'name')
                            ->default(auth()->id()),
                        Forms\Components\Select::make('approved_by')
                            ->label('اعتمده')
                            ->relationship('approvedBy', 'name'),
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
                Tables\Columns\TextColumn::make('adjustment_number')
                    ->label('رقم التسوية')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('المستودع')
                    ->sortable(),
                Tables\Columns\TextColumn::make('adjustment_date')
                    ->label('التاريخ')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('adjustment_type')
                    ->label('النوع')
                    ->formatStateUsing(fn ($state) => StockAdjustment::ADJUSTMENT_TYPES[$state] ?? $state),
                Tables\Columns\TextColumn::make('total_increase_value')
                    ->label('الزيادة')
                    ->money('JOD')
                    ->color('success'),
                Tables\Columns\TextColumn::make('total_decrease_value')
                    ->label('النقص')
                    ->money('JOD')
                    ->color('danger'),
                Tables\Columns\TextColumn::make('net_adjustment_value')
                    ->label('الصافي')
                    ->money('JOD'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors([
                        'gray' => 'draft',
                        'info' => 'submitted',
                        'success' => 'approved',
                        'primary' => 'posted',
                        'danger' => 'cancelled',
                    ])
                    ->formatStateUsing(fn ($state) => StockAdjustment::STATUSES[$state] ?? $state),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('warehouse_id')
                    ->label('المستودع')
                    ->relationship('warehouse', 'name'),
                Tables\Filters\SelectFilter::make('adjustment_type')
                    ->label('النوع')
                    ->options(StockAdjustment::ADJUSTMENT_TYPES),
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(StockAdjustment::STATUSES),
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
            'index' => Pages\ListStockAdjustments::route('/'),
            'create' => Pages\CreateStockAdjustment::route('/create'),
            'edit' => Pages\EditStockAdjustment::route('/{record}/edit'),
        ];
    }
}
