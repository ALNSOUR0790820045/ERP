<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockTransferResource\Pages;
use App\Models\StockTransfer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StockTransferResource extends Resource
{
    protected static ?string $model = StockTransfer::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static ?string $navigationGroup = 'المشتريات والمخازن';
    protected static ?string $modelLabel = 'تحويل مخزون';
    protected static ?string $pluralModelLabel = 'تحويلات المخزون';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات التحويل')
                    ->schema([
                        Forms\Components\TextInput::make('transfer_number')
                            ->label('رقم التحويل')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->default(fn () => 'TRF-' . date('Ymd') . '-' . rand(1000, 9999)),
                        Forms\Components\Select::make('from_warehouse_id')
                            ->label('من مستودع')
                            ->relationship('fromWarehouse', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('to_warehouse_id')
                            ->label('إلى مستودع')
                            ->relationship('toWarehouse', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->different('from_warehouse_id'),
                        Forms\Components\Select::make('project_id')
                            ->label('المشروع')
                            ->relationship('project', 'name')
                            ->searchable(),
                    ])->columns(2),

                Forms\Components\Section::make('التواريخ')
                    ->schema([
                        Forms\Components\DatePicker::make('transfer_date')
                            ->label('تاريخ التحويل')
                            ->required()
                            ->default(now()),
                        Forms\Components\DatePicker::make('expected_arrival_date')
                            ->label('تاريخ الوصول المتوقع'),
                        Forms\Components\DatePicker::make('actual_arrival_date')
                            ->label('تاريخ الوصول الفعلي'),
                    ])->columns(3),

                Forms\Components\Section::make('الحالة')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options(StockTransfer::STATUSES)
                            ->default('draft')
                            ->required(),
                        Forms\Components\Select::make('requested_by')
                            ->label('طلبه')
                            ->relationship('requestedBy', 'name')
                            ->default(auth()->id()),
                        Forms\Components\Select::make('approved_by')
                            ->label('اعتمده')
                            ->relationship('approvedBy', 'name'),
                        Forms\Components\Select::make('shipped_by')
                            ->label('شحنه')
                            ->relationship('shippedBy', 'name'),
                        Forms\Components\Select::make('received_by')
                            ->label('استلمه')
                            ->relationship('receivedBy', 'name'),
                    ])->columns(3),

                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات')
                    ->rows(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transfer_number')
                    ->label('رقم التحويل')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('fromWarehouse.name')
                    ->label('من')
                    ->sortable(),
                Tables\Columns\TextColumn::make('toWarehouse.name')
                    ->label('إلى')
                    ->sortable(),
                Tables\Columns\TextColumn::make('transfer_date')
                    ->label('التاريخ')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_items')
                    ->label('عدد الأصناف'),
                Tables\Columns\TextColumn::make('total_value')
                    ->label('القيمة')
                    ->money('JOD'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors([
                        'gray' => 'draft',
                        'warning' => 'pending_approval',
                        'info' => 'approved',
                        'primary' => 'in_transit',
                        'success' => 'received',
                        'danger' => 'cancelled',
                    ])
                    ->formatStateUsing(fn ($state) => StockTransfer::STATUSES[$state] ?? $state),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('from_warehouse_id')
                    ->label('من مستودع')
                    ->relationship('fromWarehouse', 'name'),
                Tables\Filters\SelectFilter::make('to_warehouse_id')
                    ->label('إلى مستودع')
                    ->relationship('toWarehouse', 'name'),
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(StockTransfer::STATUSES),
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
            'index' => Pages\ListStockTransfers::route('/'),
            'create' => Pages\CreateStockTransfer::route('/create'),
            'edit' => Pages\EditStockTransfer::route('/{record}/edit'),
        ];
    }
}
