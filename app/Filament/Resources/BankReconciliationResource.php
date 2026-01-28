<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BankReconciliationResource\Pages;
use App\Models\BankReconciliation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BankReconciliationResource extends Resource
{
    protected static ?string $model = BankReconciliation::class;

    protected static ?string $navigationIcon = 'heroicon-o-scale';
    protected static ?string $navigationGroup = 'المالية';
    protected static ?string $modelLabel = 'مطابقة بنكية';
    protected static ?string $pluralModelLabel = 'مطابقات البنوك';
    protected static ?int $navigationSort = 15;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات المطابقة')
                    ->schema([
                        Forms\Components\TextInput::make('reconciliation_number')
                            ->label('رقم المطابقة')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->default(fn () => 'REC-' . date('Ym') . '-' . rand(100, 999)),
                        Forms\Components\Select::make('bank_account_id')
                            ->label('الحساب البنكي')
                            ->relationship('bankAccount', 'account_name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\DatePicker::make('statement_date')
                            ->label('تاريخ كشف الحساب')
                            ->required(),
                        Forms\Components\Select::make('fiscal_period_id')
                            ->label('الفترة المالية')
                            ->relationship('fiscalPeriod', 'name')
                            ->searchable(),
                    ])->columns(2),

                Forms\Components\Section::make('الأرصدة')
                    ->schema([
                        Forms\Components\TextInput::make('statement_balance')
                            ->label('رصيد كشف البنك')
                            ->numeric()
                            ->required()
                            ->prefix('JOD')
                            ->live(onBlur: true),
                        Forms\Components\TextInput::make('book_balance')
                            ->label('رصيد الدفاتر')
                            ->numeric()
                            ->required()
                            ->prefix('JOD')
                            ->live(onBlur: true),
                    ])->columns(2),

                Forms\Components\Section::make('التسويات على كشف البنك')
                    ->description('إضافة للرصيد البنكي')
                    ->schema([
                        Forms\Components\TextInput::make('deposits_in_transit')
                            ->label('إيداعات في الطريق')
                            ->numeric()
                            ->default(0)
                            ->prefix('JOD')
                            ->helperText('إيداعات سجلت بالدفاتر ولم تظهر بالبنك'),
                        Forms\Components\TextInput::make('outstanding_checks')
                            ->label('شيكات معلقة')
                            ->numeric()
                            ->default(0)
                            ->prefix('JOD')
                            ->helperText('شيكات صدرت ولم تصرف بعد'),
                    ])->columns(2),

                Forms\Components\Section::make('التسويات على الدفاتر')
                    ->description('تعديل على رصيد الدفاتر')
                    ->schema([
                        Forms\Components\TextInput::make('bank_charges')
                            ->label('رسوم بنكية')
                            ->numeric()
                            ->default(0)
                            ->prefix('JOD'),
                        Forms\Components\TextInput::make('bank_interest')
                            ->label('فوائد بنكية')
                            ->numeric()
                            ->default(0)
                            ->prefix('JOD'),
                        Forms\Components\TextInput::make('other_adjustments')
                            ->label('تعديلات أخرى')
                            ->numeric()
                            ->default(0)
                            ->prefix('JOD'),
                    ])->columns(3),

                Forms\Components\Section::make('النتيجة')
                    ->schema([
                        Forms\Components\TextInput::make('adjusted_book_balance')
                            ->label('رصيد الدفاتر المعدل')
                            ->numeric()
                            ->disabled()
                            ->prefix('JOD'),
                        Forms\Components\TextInput::make('difference')
                            ->label('الفرق')
                            ->numeric()
                            ->disabled()
                            ->prefix('JOD'),
                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options(BankReconciliation::STATUSES)
                            ->default('draft')
                            ->required(),
                    ])->columns(3),

                Forms\Components\Section::make('الاعتماد')
                    ->schema([
                        Forms\Components\Select::make('prepared_by')
                            ->label('أعده')
                            ->relationship('preparedBy', 'name')
                            ->default(auth()->id()),
                        Forms\Components\Select::make('approved_by')
                            ->label('اعتمده')
                            ->relationship('approvedBy', 'name'),
                        Forms\Components\DatePicker::make('approval_date')
                            ->label('تاريخ الاعتماد'),
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
                Tables\Columns\TextColumn::make('reconciliation_number')
                    ->label('رقم المطابقة')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('bankAccount.account_name')
                    ->label('الحساب البنكي')
                    ->sortable(),
                Tables\Columns\TextColumn::make('statement_date')
                    ->label('تاريخ الكشف')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('statement_balance')
                    ->label('رصيد البنك')
                    ->money('JOD'),
                Tables\Columns\TextColumn::make('book_balance')
                    ->label('رصيد الدفاتر')
                    ->money('JOD'),
                Tables\Columns\TextColumn::make('difference')
                    ->label('الفرق')
                    ->money('JOD')
                    ->color(fn ($state) => $state != 0 ? 'danger' : 'success'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors([
                        'gray' => 'draft',
                        'info' => 'in_progress',
                        'warning' => 'completed',
                        'success' => 'approved',
                    ])
                    ->formatStateUsing(fn ($state) => BankReconciliation::STATUSES[$state] ?? $state),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('bank_account_id')
                    ->label('الحساب البنكي')
                    ->relationship('bankAccount', 'account_name'),
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(BankReconciliation::STATUSES),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('calculate')
                    ->label('حساب')
                    ->icon('heroicon-o-calculator')
                    ->action(function (BankReconciliation $record) {
                        $record->calculate();
                        $record->save();
                    }),
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
            'index' => Pages\ListBankReconciliations::route('/'),
            'create' => Pages\CreateBankReconciliation::route('/create'),
            'edit' => Pages\EditBankReconciliation::route('/{record}/edit'),
        ];
    }
}
