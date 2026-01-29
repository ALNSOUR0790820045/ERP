<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContractAdvancePaymentResource\Pages;
use App\Models\ContractAdvancePayment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ContractAdvancePaymentResource extends Resource
{
    protected static ?string $model = ContractAdvancePayment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'المشاريع والعقود';
    protected static ?string $modelLabel = 'دفعة مقدمة';
    protected static ?string $pluralModelLabel = 'الدفعات المقدمة';
    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الدفعة المقدمة')
                    ->schema([
                        Forms\Components\Select::make('contract_id')
                            ->label('العقد')
                            ->relationship('contract', 'contract_number')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('advance_type')
                            ->label('نوع الدفعة')
                            ->options(ContractAdvancePayment::ADVANCE_TYPES)
                            ->required(),
                        Forms\Components\TextInput::make('advance_number')
                            ->label('رقم الدفعة')
                            ->required()
                            ->unique(ignoreRecord: true),
                        Forms\Components\DatePicker::make('advance_date')
                            ->label('تاريخ الدفعة')
                            ->required()
                            ->default(now()),
                    ])->columns(2),

                Forms\Components\Section::make('المبلغ')
                    ->schema([
                        Forms\Components\TextInput::make('advance_percentage')
                            ->label('نسبة الدفعة %')
                            ->numeric()
                            ->suffix('%')
                            ->live(onBlur: true),
                        Forms\Components\TextInput::make('advance_amount')
                            ->label('مبلغ الدفعة')
                            ->numeric()
                            ->required()
                            ->prefix('JOD'),
                        Forms\Components\TextInput::make('recovery_rate')
                            ->label('نسبة الاسترداد %')
                            ->numeric()
                            ->default(10)
                            ->suffix('%')
                            ->helperText('النسبة المستردة من كل مستخلص'),
                    ])->columns(3),

                Forms\Components\Section::make('الضمان البنكي')
                    ->schema([
                        Forms\Components\TextInput::make('bank_guarantee_number')
                            ->label('رقم الضمان البنكي')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('bank_guarantee_amount')
                            ->label('مبلغ الضمان')
                            ->numeric()
                            ->prefix('JOD'),
                        Forms\Components\DatePicker::make('bank_guarantee_expiry')
                            ->label('تاريخ انتهاء الضمان'),
                    ])->columns(3),

                Forms\Components\Section::make('الاسترداد')
                    ->schema([
                        Forms\Components\TextInput::make('recovered_amount')
                            ->label('المسترد')
                            ->numeric()
                            ->default(0)
                            ->prefix('JOD')
                            ->disabled(),
                        Forms\Components\TextInput::make('balance_amount')
                            ->label('الرصيد المتبقي')
                            ->numeric()
                            ->prefix('JOD')
                            ->disabled(),
                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options(ContractAdvancePayment::STATUSES)
                            ->default('pending')
                            ->required(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('advance_number')
                    ->label('رقم الدفعة')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('contract.contract_number')
                    ->label('العقد')
                    ->searchable(),
                Tables\Columns\TextColumn::make('advance_type')
                    ->label('النوع')
                    ->formatStateUsing(fn ($state) => ContractAdvancePayment::ADVANCE_TYPES[$state] ?? $state),
                Tables\Columns\TextColumn::make('advance_date')
                    ->label('التاريخ')
                    ->date(),
                Tables\Columns\TextColumn::make('advance_amount')
                    ->label('المبلغ')
                    ->money('JOD'),
                Tables\Columns\TextColumn::make('recovered_amount')
                    ->label('المسترد')
                    ->money('JOD'),
                Tables\Columns\TextColumn::make('balance_amount')
                    ->label('المتبقي')
                    ->money('JOD')
                    ->color('warning'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors([
                        'gray' => 'pending',
                        'info' => 'paid',
                        'warning' => 'partially_recovered',
                        'success' => 'fully_recovered',
                    ])
                    ->formatStateUsing(fn ($state) => ContractAdvancePayment::STATUSES[$state] ?? $state),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('contract_id')
                    ->label('العقد')
                    ->relationship('contract', 'contract_number'),
                Tables\Filters\SelectFilter::make('advance_type')
                    ->label('النوع')
                    ->options(ContractAdvancePayment::ADVANCE_TYPES),
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(ContractAdvancePayment::STATUSES),
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
            'index' => Pages\ListContractAdvancePayments::route('/'),
            'create' => Pages\CreateContractAdvancePayment::route('/create'),
            'edit' => Pages\EditContractAdvancePayment::route('/{record}/edit'),
        ];
    }
}
