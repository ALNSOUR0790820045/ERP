<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenderBondResource\Pages;
use App\Models\TenderBond;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TenderBondResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = TenderBond::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    
    protected static ?string $navigationGroup = 'العطاءات والمناقصات';
    
    protected static ?string $navigationLabel = 'كفالات العطاءات';
    
    protected static ?string $modelLabel = 'كفالة';
    
    protected static ?string $pluralModelLabel = 'كفالات العطاءات';
    
    protected static ?int $navigationSort = 13;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('بيانات الكفالة')
                    ->schema([
                        Forms\Components\Select::make('tender_id')
                            ->relationship('tender', 'name_ar')
                            ->label('العطاء')
                            ->required()
                            ->searchable(),
                        Forms\Components\Select::make('bond_type')
                            ->label('نوع الكفالة')
                            ->options(TenderBond::BOND_TYPES)
                            ->required(),
                        Forms\Components\TextInput::make('bond_number')
                            ->label('رقم الكفالة')
                            ->required()
                            ->maxLength(100),
                        Forms\Components\DatePicker::make('issue_date')
                            ->label('تاريخ الإصدار')
                            ->required(),
                        Forms\Components\DatePicker::make('expiry_date')
                            ->label('تاريخ الانتهاء')
                            ->required(),
                        Forms\Components\TextInput::make('amount')
                            ->label('المبلغ')
                            ->required()
                            ->numeric(),
                        Forms\Components\Select::make('currency_id')
                            ->relationship('currency', 'code')
                            ->label('العملة')
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options(TenderBond::STATUSES)
                            ->default('draft'),
                    ])->columns(2),
                    
                Forms\Components\Section::make('الجهة المصدرة')
                    ->schema([
                        Forms\Components\Select::make('issuer_type')
                            ->label('نوع الجهة')
                            ->options(TenderBond::ISSUER_TYPES)
                            ->required(),
                        Forms\Components\TextInput::make('issuer_name')
                            ->label('اسم الجهة')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('issuer_branch')
                            ->label('الفرع')
                            ->maxLength(255),
                        Forms\Components\Textarea::make('issuer_address')
                            ->label('العنوان')
                            ->rows(2),
                        Forms\Components\TextInput::make('issuer_contact')
                            ->label('جهة الاتصال')
                            ->maxLength(255),
                    ])->columns(2),
                    
                Forms\Components\Section::make('المستفيد')
                    ->schema([
                        Forms\Components\TextInput::make('beneficiary_name')
                            ->label('اسم المستفيد')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('beneficiary_address')
                            ->label('عنوان المستفيد')
                            ->rows(2),
                    ])->columns(2),
                    
                Forms\Components\Section::make('التكاليف')
                    ->schema([
                        Forms\Components\TextInput::make('issuance_fee')
                            ->label('رسوم الإصدار')
                            ->numeric(),
                        Forms\Components\TextInput::make('commission_rate')
                            ->label('نسبة العمولة')
                            ->numeric()
                            ->suffix('%'),
                        Forms\Components\TextInput::make('total_cost')
                            ->label('التكلفة الإجمالية')
                            ->numeric(),
                    ])->columns(3),
                    
                Forms\Components\Section::make('المرفقات والملاحظات')
                    ->schema([
                        Forms\Components\FileUpload::make('document_path')
                            ->label('ملف الكفالة')
                            ->directory('tender-bonds')
                            ->acceptedFileTypes(['application/pdf', 'image/*']),
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
                Tables\Columns\TextColumn::make('tender.name_ar')
                    ->label('العطاء')
                    ->searchable()
                    ->limit(25),
                Tables\Columns\TextColumn::make('bond_type')
                    ->label('نوع الكفالة')
                    ->badge()
                    ->formatStateUsing(fn ($state) => TenderBond::BOND_TYPES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'bid_security' => 'primary',
                        'performance_bond' => 'success',
                        'advance_payment_bond' => 'warning',
                        'retention_bond' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('bond_number')
                    ->label('رقم الكفالة')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('المبلغ')
                    ->money(fn ($record) => $record->currency?->code ?? 'JOD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('issue_date')
                    ->label('تاريخ الإصدار')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('expiry_date')
                    ->label('تاريخ الانتهاء')
                    ->date()
                    ->sortable()
                    ->color(fn ($record) => $record->is_expiring ? 'danger' : null),
                Tables\Columns\TextColumn::make('issuer_name')
                    ->label('الجهة المصدرة')
                    ->limit(20),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn ($state) => TenderBond::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'issued' => 'success',
                        'submitted' => 'primary',
                        'released' => 'gray',
                        'expired' => 'danger',
                        'claimed' => 'danger',
                        default => 'warning',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('bond_type')
                    ->label('نوع الكفالة')
                    ->options(TenderBond::BOND_TYPES),
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(TenderBond::STATUSES),
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenderBonds::route('/'),
            'create' => Pages\CreateTenderBond::route('/create'),
            'edit' => Pages\EditTenderBond::route('/{record}/edit'),
        ];
    }
}
