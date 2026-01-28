<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'العملاء (CRM)';

    protected static ?string $modelLabel = 'عميل';

    protected static ?string $pluralModelLabel = 'العملاء';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الشركة')
                    ->schema([
                        Forms\Components\TextInput::make('customer_code')
                            ->label('رمز العميل')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->default(fn () => Customer::generateCode()),
                        Forms\Components\TextInput::make('company_name')
                            ->label('اسم الشركة (عربي)')
                            ->required(),
                        Forms\Components\TextInput::make('company_name_en')
                            ->label('اسم الشركة (إنجليزي)'),
                        Forms\Components\Select::make('customer_type')
                            ->label('نوع العميل')
                            ->options([
                                'government' => 'حكومي',
                                'semi_government' => 'شبه حكومي',
                                'private' => 'قطاع خاص',
                                'international' => 'دولي',
                                'individual' => 'أفراد',
                            ])
                            ->default('private')
                            ->required(),
                        Forms\Components\TextInput::make('industry')
                            ->label('القطاع'),
                        Forms\Components\Select::make('classification')
                            ->label('التصنيف')
                            ->options([
                                'vip' => 'VIP',
                                'a' => 'A - رئيسي',
                                'b' => 'B - مهم',
                                'c' => 'C - عادي',
                            ])
                            ->default('c'),
                    ])->columns(3),

                Forms\Components\Section::make('معلومات قانونية')
                    ->schema([
                        Forms\Components\TextInput::make('tax_number')
                            ->label('الرقم الضريبي'),
                        Forms\Components\TextInput::make('commercial_reg')
                            ->label('السجل التجاري'),
                    ])->columns(2),

                Forms\Components\Section::make('العنوان')
                    ->schema([
                        Forms\Components\Textarea::make('address')
                            ->label('العنوان')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('city')
                            ->label('المدينة'),
                        Forms\Components\TextInput::make('country')
                            ->label('الدولة')
                            ->default('الأردن'),
                        Forms\Components\TextInput::make('postal_code')
                            ->label('الرمز البريدي'),
                    ])->columns(3),

                Forms\Components\Section::make('معلومات الاتصال')
                    ->schema([
                        Forms\Components\TextInput::make('phone')
                            ->label('الهاتف')
                            ->tel(),
                        Forms\Components\TextInput::make('fax')
                            ->label('الفاكس'),
                        Forms\Components\TextInput::make('email')
                            ->label('البريد الإلكتروني')
                            ->email(),
                        Forms\Components\TextInput::make('website')
                            ->label('الموقع الإلكتروني')
                            ->url(),
                    ])->columns(4),

                Forms\Components\Section::make('البيانات المالية')
                    ->schema([
                        Forms\Components\TextInput::make('credit_limit')
                            ->label('حد الائتمان')
                            ->numeric()
                            ->default(0),
                        Forms\Components\TextInput::make('payment_terms_days')
                            ->label('شروط الدفع (أيام)')
                            ->numeric()
                            ->default(30),
                        Forms\Components\TextInput::make('currency')
                            ->label('العملة')
                            ->default('JOD'),
                        Forms\Components\TextInput::make('bank_name')
                            ->label('البنك'),
                        Forms\Components\TextInput::make('bank_account')
                            ->label('رقم الحساب'),
                        Forms\Components\TextInput::make('iban')
                            ->label('IBAN'),
                    ])->columns(3),

                Forms\Components\Section::make('الحالة')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options([
                                'active' => 'نشط',
                                'inactive' => 'غير نشط',
                                'blocked' => 'محظور',
                            ])
                            ->default('active'),
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer_code')
                    ->label('الرمز')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('company_name')
                    ->label('اسم الشركة')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer_type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'government' => 'حكومي',
                        'semi_government' => 'شبه حكومي',
                        'private' => 'خاص',
                        'international' => 'دولي',
                        'individual' => 'أفراد',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('classification')
                    ->label('التصنيف')
                    ->badge()
                    ->colors([
                        'danger' => 'vip',
                        'warning' => 'a',
                        'success' => 'b',
                        'gray' => 'c',
                    ]),
                Tables\Columns\TextColumn::make('city')
                    ->label('المدينة'),
                Tables\Columns\TextColumn::make('phone')
                    ->label('الهاتف'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors([
                        'success' => 'active',
                        'gray' => 'inactive',
                        'danger' => 'blocked',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('customer_type')
                    ->label('نوع العميل')
                    ->options([
                        'government' => 'حكومي',
                        'semi_government' => 'شبه حكومي',
                        'private' => 'خاص',
                        'international' => 'دولي',
                        'individual' => 'أفراد',
                    ]),
                Tables\Filters\SelectFilter::make('classification')
                    ->label('التصنيف')
                    ->options([
                        'vip' => 'VIP',
                        'a' => 'A',
                        'b' => 'B',
                        'c' => 'C',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'active' => 'نشط',
                        'inactive' => 'غير نشط',
                        'blocked' => 'محظور',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
