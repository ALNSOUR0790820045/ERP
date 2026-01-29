<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenderBondWithdrawalResource\Pages;
use App\Models\Tenders\TenderBondWithdrawal;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TenderBondWithdrawalResource extends Resource
{
    protected static ?string $model = TenderBondWithdrawal::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-uturn-left';
    protected static ?string $navigationGroup = 'العطاءات والمناقصات';
    protected static ?string $navigationLabel = 'سحب الكفالات';
    protected static ?string $modelLabel = 'سحب كفالة';
    protected static ?string $pluralModelLabel = 'سحب الكفالات';
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('بيانات السحب')->schema([
                Forms\Components\Select::make('bond_id')
                    ->label('الكفالة')
                    ->relationship('bond', 'bond_number')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('withdrawal_reason')
                    ->label('سبب السحب')
                    ->options(TenderBondWithdrawal::WITHDRAWAL_REASONS)
                    ->required(),
                Forms\Components\DatePicker::make('request_date')
                    ->label('تاريخ الطلب')
                    ->required()
                    ->default(now()),
                Forms\Components\DatePicker::make('withdrawal_date')
                    ->label('تاريخ السحب'),
            ])->columns(2),

            Forms\Components\Section::make('خطاب الإفراج')->schema([
                Forms\Components\TextInput::make('release_letter_number')
                    ->label('رقم خطاب الإفراج')
                    ->maxLength(100),
                Forms\Components\DatePicker::make('release_letter_date')
                    ->label('تاريخ خطاب الإفراج'),
                Forms\Components\FileUpload::make('release_letter_path')
                    ->label('صورة خطاب الإفراج')
                    ->directory('tender-bond-withdrawals'),
                Forms\Components\FileUpload::make('original_bond_path')
                    ->label('صورة الكفالة الأصلية')
                    ->directory('tender-bond-withdrawals'),
            ])->columns(2),

            Forms\Components\Section::make('الاسترداد')->schema([
                Forms\Components\TextInput::make('refund_amount')
                    ->label('مبلغ الاسترداد')
                    ->numeric()
                    ->prefix('JOD'),
                Forms\Components\DatePicker::make('refund_date')
                    ->label('تاريخ الاسترداد'),
                Forms\Components\Select::make('status')
                    ->label('الحالة')
                    ->options(TenderBondWithdrawal::STATUSES)
                    ->default('pending')
                    ->required(),
            ])->columns(3),

            Forms\Components\Textarea::make('notes')
                ->label('ملاحظات')
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('bond.bond_number')
                    ->label('رقم الكفالة')
                    ->searchable(),
                Tables\Columns\TextColumn::make('withdrawal_reason')
                    ->label('السبب')
                    ->badge()
                    ->formatStateUsing(fn($state) => TenderBondWithdrawal::WITHDRAWAL_REASONS[$state] ?? $state),
                Tables\Columns\TextColumn::make('request_date')
                    ->label('تاريخ الطلب')
                    ->date(),
                Tables\Columns\TextColumn::make('withdrawal_date')
                    ->label('تاريخ السحب')
                    ->date(),
                Tables\Columns\TextColumn::make('refund_amount')
                    ->label('الاسترداد')
                    ->money('JOD'),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn($state) => TenderBondWithdrawal::STATUSES[$state] ?? $state)
                    ->color(fn($state) => match($state) {
                        'withdrawn', 'returned' => 'success',
                        'in_progress' => 'warning',
                        'pending' => 'info',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(TenderBondWithdrawal::STATUSES),
                Tables\Filters\SelectFilter::make('withdrawal_reason')
                    ->label('السبب')
                    ->options(TenderBondWithdrawal::WITHDRAWAL_REASONS),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('complete')
                    ->label('إتمام')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn($record) => in_array($record->status, ['pending', 'in_progress']))
                    ->action(fn($record) => $record->complete(auth()->user())),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenderBondWithdrawals::route('/'),
            'create' => Pages\CreateTenderBondWithdrawal::route('/create'),
            'edit' => Pages\EditTenderBondWithdrawal::route('/{record}/edit'),
        ];
    }
}
