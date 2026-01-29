<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenderBondRenewalResource\Pages;
use App\Models\Tenders\TenderBondRenewal;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TenderBondRenewalResource extends Resource
{
    protected static ?string $model = TenderBondRenewal::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationGroup = 'العطاءات والمناقصات';
    protected static ?string $navigationLabel = 'تجديد الكفالات';
    protected static ?string $modelLabel = 'تجديد كفالة';
    protected static ?string $pluralModelLabel = 'تجديدات الكفالات';
    protected static ?int $navigationSort = 9;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('بيانات التجديد')->schema([
                Forms\Components\Select::make('bond_id')
                    ->label('الكفالة')
                    ->relationship('bond', 'bond_number')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('renewal_number')
                    ->label('رقم التجديد')
                    ->numeric()
                    ->required(),
                Forms\Components\DatePicker::make('request_date')
                    ->label('تاريخ الطلب')
                    ->required()
                    ->default(now()),
            ])->columns(3),

            Forms\Components\Section::make('تواريخ الصلاحية')->schema([
                Forms\Components\DatePicker::make('current_expiry_date')
                    ->label('تاريخ الانتهاء الحالي')
                    ->required(),
                Forms\Components\DatePicker::make('new_expiry_date')
                    ->label('تاريخ الانتهاء الجديد')
                    ->required(),
                Forms\Components\TextInput::make('extension_days')
                    ->label('أيام التمديد')
                    ->numeric()
                    ->required(),
            ])->columns(3),

            Forms\Components\Section::make('التكاليف')->schema([
                Forms\Components\TextInput::make('renewal_fee')
                    ->label('رسوم التجديد')
                    ->numeric()
                    ->prefix('JOD'),
                Forms\Components\TextInput::make('commission_amount')
                    ->label('العمولة')
                    ->numeric()
                    ->prefix('JOD'),
                Forms\Components\TextInput::make('new_bond_number')
                    ->label('رقم الكفالة الجديد')
                    ->maxLength(100),
            ])->columns(3),

            Forms\Components\Section::make('المعلومات الإضافية')->schema([
                Forms\Components\Textarea::make('reason')
                    ->label('سبب التجديد')
                    ->rows(3)
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label('الحالة')
                    ->options(TenderBondRenewal::STATUSES)
                    ->default('pending')
                    ->required(),
                Forms\Components\FileUpload::make('document_path')
                    ->label('وثيقة التجديد')
                    ->directory('tender-bond-renewals'),
                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات')
                    ->rows(2),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('bond.bond_number')
                    ->label('رقم الكفالة')
                    ->searchable(),
                Tables\Columns\TextColumn::make('renewal_number')
                    ->label('رقم التجديد')
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_expiry_date')
                    ->label('الانتهاء الحالي')
                    ->date(),
                Tables\Columns\TextColumn::make('new_expiry_date')
                    ->label('الانتهاء الجديد')
                    ->date(),
                Tables\Columns\TextColumn::make('extension_days')
                    ->label('الأيام')
                    ->suffix(' يوم'),
                Tables\Columns\TextColumn::make('renewal_fee')
                    ->label('الرسوم')
                    ->money('JOD'),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn($state) => TenderBondRenewal::STATUSES[$state] ?? $state)
                    ->color(fn($state) => match($state) {
                        'renewed' => 'success',
                        'processing' => 'warning',
                        'pending' => 'info',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(TenderBondRenewal::STATUSES),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('موافقة')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn($record) => $record->status === 'pending')
                    ->action(fn($record) => $record->approve(auth()->user())),
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
            'index' => Pages\ListTenderBondRenewals::route('/'),
            'create' => Pages\CreateTenderBondRenewal::route('/create'),
            'edit' => Pages\EditTenderBondRenewal::route('/{record}/edit'),
        ];
    }
}
