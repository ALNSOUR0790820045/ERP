<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JournalVoucherResource\Pages;
use App\Models\JournalVoucher;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class JournalVoucherResource extends Resource
{
    protected static ?string $model = JournalVoucher::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'المالية والمحاسبة';
    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return 'قيد يومية';
    }

    public static function getPluralModelLabel(): string
    {
        return 'قيود اليومية';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('بيانات القيد')->schema([
                Forms\Components\TextInput::make('voucher_number')
                    ->label('رقم القيد')
                    ->required()
                    ->unique(ignoreRecord: true),
                Forms\Components\DatePicker::make('voucher_date')
                    ->label('التاريخ')
                    ->required()
                    ->default(now()),
                Forms\Components\Select::make('voucher_type')
                    ->label('نوع القيد')
                    ->options([
                        'general' => 'عام',
                        'opening' => 'افتتاحي',
                        'adjustment' => 'تسوية',
                        'closing' => 'إقفال',
                    ])
                    ->default('general'),
                Forms\Components\Select::make('company_id')
                    ->label('الشركة')
                    ->relationship('company', 'name_ar')
                    ->required(),
                Forms\Components\Textarea::make('description')
                    ->label('البيان')
                    ->required()
                    ->columnSpan(2),
            ])->columns(2),

            Forms\Components\Section::make('بنود القيد')->schema([
                Forms\Components\Repeater::make('lines')
                    ->relationship()
                    ->schema([
                        Forms\Components\Select::make('account_id')
                            ->label('الحساب')
                            ->relationship('account', 'name_ar')
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('cost_center_id')
                            ->label('مركز التكلفة')
                            ->relationship('costCenter', 'name_ar'),
                        Forms\Components\TextInput::make('description')
                            ->label('البيان'),
                        Forms\Components\TextInput::make('debit_amount')
                            ->label('مدين')
                            ->numeric()
                            ->default(0),
                        Forms\Components\TextInput::make('credit_amount')
                            ->label('دائن')
                            ->numeric()
                            ->default(0),
                    ])
                    ->columns(5)
                    ->minItems(2)
                    ->columnSpanFull(),
            ]),

            Forms\Components\Section::make('الإجماليات')->schema([
                Forms\Components\TextInput::make('total_debit')
                    ->label('إجمالي المدين')
                    ->disabled(),
                Forms\Components\TextInput::make('total_credit')
                    ->label('إجمالي الدائن')
                    ->disabled(),
                Forms\Components\Select::make('status')
                    ->label('الحالة')
                    ->options([
                        'draft' => 'مسودة',
                        'pending' => 'قيد الاعتماد',
                        'approved' => 'معتمد',
                        'posted' => 'مرحل',
                        'cancelled' => 'ملغي',
                    ])
                    ->default('draft'),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('voucher_number')->label('رقم القيد')->searchable(),
                Tables\Columns\TextColumn::make('voucher_date')->label('التاريخ')->date(),
                Tables\Columns\TextColumn::make('description')->label('البيان')->limit(50),
                Tables\Columns\TextColumn::make('total_debit')->label('المدين')->money('JOD'),
                Tables\Columns\TextColumn::make('total_credit')->label('الدائن')->money('JOD'),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'pending' => 'warning',
                        'approved' => 'info',
                        'posted' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'مسودة',
                        'posted' => 'مرحل',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJournalVouchers::route('/'),
            'create' => Pages\CreateJournalVoucher::route('/create'),
            'edit' => Pages\EditJournalVoucher::route('/{record}/edit'),
        ];
    }
}
