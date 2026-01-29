<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContractRetentionResource\Pages;
use App\Models\ContractRetention;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ContractRetentionResource extends Resource
{
    protected static ?string $model = ContractRetention::class;

    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';
    protected static ?string $navigationGroup = 'المشاريع والعقود';
    protected static ?string $modelLabel = 'محتجز عقد';
    protected static ?string $pluralModelLabel = 'محتجزات العقود';
    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات المحتجز')
                    ->schema([
                        Forms\Components\Select::make('contract_id')
                            ->label('العقد')
                            ->relationship('contract', 'contract_number')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('retention_type')
                            ->label('نوع المحتجز')
                            ->options(ContractRetention::RETENTION_TYPES)
                            ->required(),
                        Forms\Components\TextInput::make('retention_rate')
                            ->label('نسبة الاحتجاز %')
                            ->numeric()
                            ->required()
                            ->default(10)
                            ->suffix('%'),
                        Forms\Components\TextInput::make('max_retention_amount')
                            ->label('الحد الأعلى للاحتجاز')
                            ->numeric()
                            ->prefix('JOD')
                            ->helperText('اتركه فارغاً إذا لا يوجد حد أعلى'),
                    ])->columns(2),

                Forms\Components\Section::make('المبالغ')
                    ->schema([
                        Forms\Components\TextInput::make('total_retained_amount')
                            ->label('إجمالي المحتجز')
                            ->numeric()
                            ->default(0)
                            ->prefix('JOD')
                            ->disabled(),
                        Forms\Components\TextInput::make('released_amount')
                            ->label('المحرر')
                            ->numeric()
                            ->default(0)
                            ->prefix('JOD')
                            ->disabled(),
                        Forms\Components\TextInput::make('balance_amount')
                            ->label('الرصيد')
                            ->numeric()
                            ->default(0)
                            ->prefix('JOD')
                            ->disabled(),
                    ])->columns(3),

                Forms\Components\Section::make('شروط التحرير')
                    ->schema([
                        Forms\Components\Textarea::make('release_conditions')
                            ->label('شروط التحرير')
                            ->rows(2)
                            ->helperText('شروط تحرير المحتجز مثل: بعد الاستلام الابتدائي، نهاية فترة الصيانة'),
                        Forms\Components\Textarea::make('release_schedule')
                            ->label('جدول التحرير')
                            ->rows(2)
                            ->helperText('مثال: 50% عند الاستلام الابتدائي، 50% نهاية فترة الضمان'),
                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options(ContractRetention::STATUSES)
                            ->default('active')
                            ->required(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('contract.contract_number')
                    ->label('العقد')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('retention_type')
                    ->label('النوع')
                    ->formatStateUsing(fn ($state) => ContractRetention::RETENTION_TYPES[$state] ?? $state),
                Tables\Columns\TextColumn::make('retention_rate')
                    ->label('النسبة')
                    ->suffix('%'),
                Tables\Columns\TextColumn::make('total_retained_amount')
                    ->label('المحتجز')
                    ->money('JOD'),
                Tables\Columns\TextColumn::make('released_amount')
                    ->label('المحرر')
                    ->money('JOD'),
                Tables\Columns\TextColumn::make('balance_amount')
                    ->label('الرصيد')
                    ->money('JOD')
                    ->color('warning'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'partially_released',
                        'gray' => 'fully_released',
                    ])
                    ->formatStateUsing(fn ($state) => ContractRetention::STATUSES[$state] ?? $state),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('contract_id')
                    ->label('العقد')
                    ->relationship('contract', 'contract_number'),
                Tables\Filters\SelectFilter::make('retention_type')
                    ->label('النوع')
                    ->options(ContractRetention::RETENTION_TYPES),
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(ContractRetention::STATUSES),
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
            'index' => Pages\ListContractRetentions::route('/'),
            'create' => Pages\CreateContractRetention::route('/create'),
            'edit' => Pages\EditContractRetention::route('/{record}/edit'),
        ];
    }
}
