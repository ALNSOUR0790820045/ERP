<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierResource\Pages;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationGroup = 'المشتريات';
    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return 'مورد';
    }

    public static function getPluralModelLabel(): string
    {
        return 'الموردين';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('البيانات الأساسية')->schema([
                Forms\Components\TextInput::make('code')
                    ->label('كود المورد')
                    ->required()
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('name_ar')
                    ->label('الاسم بالعربية')
                    ->required(),
                Forms\Components\TextInput::make('name_en')
                    ->label('الاسم بالإنجليزية'),
                Forms\Components\Select::make('supplier_type')
                    ->label('نوع المورد')
                    ->options([
                        'supplier' => 'مورد',
                        'subcontractor' => 'مقاول من الباطن',
                        'service' => 'خدمات',
                    ])
                    ->default('supplier'),
                Forms\Components\TextInput::make('tax_number')
                    ->label('الرقم الضريبي'),
                Forms\Components\TextInput::make('commercial_register')
                    ->label('السجل التجاري'),
            ])->columns(2),

            Forms\Components\Section::make('معلومات الاتصال')->schema([
                Forms\Components\Textarea::make('address')
                    ->label('العنوان')
                    ->rows(2)
                    ->columnSpan(2),
                Forms\Components\TextInput::make('phone')
                    ->label('الهاتف'),
                Forms\Components\TextInput::make('fax')
                    ->label('الفاكس'),
                Forms\Components\TextInput::make('email')
                    ->label('البريد الإلكتروني')
                    ->email(),
                Forms\Components\TextInput::make('website')
                    ->label('الموقع الإلكتروني')
                    ->url(),
                Forms\Components\TextInput::make('contact_person')
                    ->label('جهة الاتصال'),
                Forms\Components\TextInput::make('contact_mobile')
                    ->label('جوال جهة الاتصال'),
            ])->columns(2),

            Forms\Components\Section::make('المعلومات المالية')->schema([
                Forms\Components\Select::make('currency_id')
                    ->label('العملة')
                    ->relationship('currency', 'name_ar'),
                Forms\Components\TextInput::make('payment_terms')
                    ->label('شروط الدفع'),
                Forms\Components\TextInput::make('credit_limit')
                    ->label('حد الائتمان')
                    ->numeric(),
                Forms\Components\TextInput::make('bank_name')
                    ->label('اسم البنك'),
                Forms\Components\TextInput::make('bank_account')
                    ->label('رقم الحساب'),
                Forms\Components\TextInput::make('iban')
                    ->label('IBAN'),
                Forms\Components\Toggle::make('is_approved')
                    ->label('معتمد'),
                Forms\Components\Toggle::make('is_active')
                    ->label('نشط')
                    ->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('الكود')->searchable(),
                Tables\Columns\TextColumn::make('name_ar')->label('الاسم')->searchable(),
                Tables\Columns\TextColumn::make('supplier_type')
                    ->label('النوع')
                    ->badge(),
                Tables\Columns\TextColumn::make('phone')->label('الهاتف'),
                Tables\Columns\TextColumn::make('contact_person')->label('جهة الاتصال'),
                Tables\Columns\IconColumn::make('is_approved')->label('معتمد')->boolean(),
                Tables\Columns\IconColumn::make('is_active')->label('نشط')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('supplier_type')
                    ->options([
                        'supplier' => 'مورد',
                        'subcontractor' => 'مقاول من الباطن',
                        'service' => 'خدمات',
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
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
        ];
    }
}
