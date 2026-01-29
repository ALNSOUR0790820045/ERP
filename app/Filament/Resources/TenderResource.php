<?php

namespace App\Filament\Resources;

use App\Enums\BondType;
use App\Enums\OwnerType;
use App\Enums\SubmissionMethod;
use App\Enums\TenderMethod;
use App\Enums\TenderResult;
use App\Enums\TenderStatus;
use App\Enums\TenderType;
use App\Filament\Resources\TenderResource\Pages;
use App\Filament\Resources\TenderResource\RelationManagers;
use App\Models\Tender;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TenderResource extends Resource
{
    protected static ?string $model = Tender::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    protected static ?string $navigationGroup = 'العطاءات والمناقصات';
    
    protected static ?string $modelLabel = 'عطاء';
    
    protected static ?string $pluralModelLabel = 'العطاءات';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Tender')
                    ->tabs([
                        // البيانات الأساسية
                        Forms\Components\Tabs\Tab::make('البيانات الأساسية')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Forms\Components\Section::make('معلومات العطاء')
                                    ->columns(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('tender_number')
                                            ->label('رقم العطاء')
                                            ->disabled()
                                            ->placeholder('تلقائي'),
                                        Forms\Components\TextInput::make('reference_number')
                                            ->label('الرقم المرجعي')
                                            ->maxLength(50),
                                        Forms\Components\Select::make('status')
                                            ->label('الحالة')
                                            ->options(TenderStatus::class)
                                            ->default(TenderStatus::NEW)
                                            ->required(),
                                        Forms\Components\TextInput::make('name_ar')
                                            ->label('اسم العطاء (عربي)')
                                            ->required()
                                            ->maxLength(255)
                                            ->columnSpan(2),
                                        Forms\Components\TextInput::make('name_en')
                                            ->label('اسم العطاء (إنجليزي)')
                                            ->maxLength(255),
                                        Forms\Components\Textarea::make('description')
                                            ->label('الوصف')
                                            ->rows(3)
                                            ->columnSpanFull(),
                                    ]),
                                    
                                Forms\Components\Section::make('تصنيف العطاء')
                                    ->columns(3)
                                    ->schema([
                                        Forms\Components\Select::make('tender_type')
                                            ->label('نوع العطاء')
                                            ->options(TenderType::class)
                                            ->required(),
                                        Forms\Components\Select::make('tender_method')
                                            ->label('أسلوب الطرح')
                                            ->options(TenderMethod::class)
                                            ->required(),
                                        Forms\Components\Select::make('project_type_id')
                                            ->label('نوع المشروع')
                                            ->relationship('projectType', 'name_ar')
                                            ->searchable()
                                            ->preload(),
                                        Forms\Components\Select::make('specialization_id')
                                            ->label('التخصص')
                                            ->relationship('specialization', 'name_ar')
                                            ->searchable()
                                            ->preload(),
                                    ]),
                            ]),
                            
                        // المالك والاستشاري
                        Forms\Components\Tabs\Tab::make('المالك والاستشاري')
                            ->icon('heroicon-o-building-office')
                            ->schema([
                                Forms\Components\Section::make('بيانات المالك')
                                    ->columns(3)
                                    ->schema([
                                        Forms\Components\Select::make('owner_type')
                                            ->label('نوع المالك')
                                            ->options(OwnerType::class)
                                            ->required(),
                                        Forms\Components\Select::make('owner_id')
                                            ->label('المالك')
                                            ->relationship('owner', 'name_ar')
                                            ->searchable()
                                            ->preload()
                                            ->createOptionForm([
                                                Forms\Components\TextInput::make('name_ar')
                                                    ->label('الاسم (عربي)')
                                                    ->required(),
                                                Forms\Components\TextInput::make('name_en')
                                                    ->label('الاسم (إنجليزي)'),
                                                Forms\Components\Select::make('owner_type')
                                                    ->label('النوع')
                                                    ->options(OwnerType::class)
                                                    ->required(),
                                            ]),
                                        Forms\Components\TextInput::make('owner_name')
                                            ->label('اسم المالك (يدوي)')
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('owner_contact_person')
                                            ->label('جهة الاتصال')
                                            ->maxLength(100),
                                        Forms\Components\TextInput::make('owner_phone')
                                            ->label('الهاتف')
                                            ->tel()
                                            ->maxLength(20),
                                        Forms\Components\TextInput::make('owner_email')
                                            ->label('البريد الإلكتروني')
                                            ->email()
                                            ->maxLength(100),
                                        Forms\Components\Textarea::make('owner_address')
                                            ->label('العنوان')
                                            ->rows(2)
                                            ->columnSpanFull(),
                                    ]),
                                    
                                Forms\Components\Section::make('بيانات الاستشاري')
                                    ->columns(3)
                                    ->schema([
                                        Forms\Components\Select::make('consultant_id')
                                            ->label('الاستشاري')
                                            ->relationship('consultant', 'name_ar')
                                            ->searchable()
                                            ->preload(),
                                        Forms\Components\TextInput::make('consultant_name')
                                            ->label('اسم الاستشاري (يدوي)')
                                            ->maxLength(255)
                                            ->columnSpan(2),
                                    ]),
                            ]),
                            
                        // الموقع
                        Forms\Components\Tabs\Tab::make('الموقع')
                            ->icon('heroicon-o-map-pin')
                            ->schema([
                                Forms\Components\Section::make('بيانات الموقع')
                                    ->columns(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('country')
                                            ->label('الدولة')
                                            ->default('الأردن')
                                            ->maxLength(50),
                                        Forms\Components\TextInput::make('city')
                                            ->label('المدينة')
                                            ->maxLength(50),
                                        Forms\Components\Textarea::make('site_address')
                                            ->label('عنوان الموقع')
                                            ->rows(2)
                                            ->columnSpanFull(),
                                        Forms\Components\TextInput::make('latitude')
                                            ->label('خط العرض')
                                            ->numeric(),
                                        Forms\Components\TextInput::make('longitude')
                                            ->label('خط الطول')
                                            ->numeric(),
                                    ]),
                            ]),
                            
                        // التواريخ
                        Forms\Components\Tabs\Tab::make('التواريخ')
                            ->icon('heroicon-o-calendar')
                            ->schema([
                                Forms\Components\Section::make('تواريخ العطاء')
                                    ->columns(3)
                                    ->schema([
                                        Forms\Components\DatePicker::make('publication_date')
                                            ->label('تاريخ النشر'),
                                        Forms\Components\DatePicker::make('documents_sale_start')
                                            ->label('بداية بيع الوثائق'),
                                        Forms\Components\DatePicker::make('documents_sale_end')
                                            ->label('نهاية بيع الوثائق'),
                                        Forms\Components\DateTimePicker::make('site_visit_date')
                                            ->label('موعد زيارة الموقع'),
                                        Forms\Components\DateTimePicker::make('questions_deadline')
                                            ->label('آخر موعد للاستفسارات'),
                                        Forms\Components\DateTimePicker::make('submission_deadline')
                                            ->label('موعد التقديم')
                                            ->required(),
                                        Forms\Components\DateTimePicker::make('opening_date')
                                            ->label('موعد الفتح'),
                                        Forms\Components\TextInput::make('validity_period')
                                            ->label('فترة الصلاحية (يوم)')
                                            ->numeric()
                                            ->default(90)
                                            ->required(),
                                        Forms\Components\DatePicker::make('expected_award_date')
                                            ->label('تاريخ الترسية المتوقع'),
                                    ]),
                            ]),
                            
                        // المالية والضمانات
                        Forms\Components\Tabs\Tab::make('المالية والضمانات')
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                Forms\Components\Section::make('القيمة والعملة')
                                    ->columns(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('estimated_value')
                                            ->label('القيمة التقديرية')
                                            ->numeric()
                                            ->prefix('د.أ'),
                                        Forms\Components\Select::make('currency_id')
                                            ->label('العملة')
                                            ->relationship('currency', 'name_ar')
                                            ->default(1),
                                        Forms\Components\TextInput::make('documents_price')
                                            ->label('ثمن الوثائق')
                                            ->numeric()
                                            ->prefix('د.أ'),
                                    ]),
                                    
                                Forms\Components\Section::make('الضمانات')
                                    ->columns(3)
                                    ->schema([
                                        Forms\Components\Select::make('bid_bond_type')
                                            ->label('نوع ضمان العطاء')
                                            ->options(BondType::class),
                                        Forms\Components\TextInput::make('bid_bond_percentage')
                                            ->label('نسبة ضمان العطاء %')
                                            ->numeric()
                                            ->suffix('%'),
                                        Forms\Components\TextInput::make('bid_bond_amount')
                                            ->label('مبلغ ضمان العطاء')
                                            ->numeric()
                                            ->prefix('د.أ'),
                                        Forms\Components\TextInput::make('performance_bond_percentage')
                                            ->label('نسبة ضمان حسن التنفيذ %')
                                            ->numeric()
                                            ->default(10)
                                            ->suffix('%'),
                                        Forms\Components\TextInput::make('advance_payment_percentage')
                                            ->label('نسبة الدفعة المقدمة %')
                                            ->numeric()
                                            ->suffix('%'),
                                        Forms\Components\TextInput::make('retention_percentage')
                                            ->label('نسبة المحتجزات %')
                                            ->numeric()
                                            ->default(10)
                                            ->suffix('%'),
                                    ]),
                            ]),
                            
                        // المتطلبات
                        Forms\Components\Tabs\Tab::make('المتطلبات')
                            ->icon('heroicon-o-clipboard-document-check')
                            ->schema([
                                Forms\Components\Section::make('الشروط الفنية')
                                    ->columns(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('required_classification')
                                            ->label('التصنيف المطلوب')
                                            ->maxLength(50),
                                        Forms\Components\TextInput::make('minimum_experience_years')
                                            ->label('الحد الأدنى للخبرة (سنة)')
                                            ->numeric(),
                                        Forms\Components\TextInput::make('minimum_similar_projects')
                                            ->label('الحد الأدنى للمشاريع المماثلة')
                                            ->numeric(),
                                        Forms\Components\TextInput::make('minimum_project_value')
                                            ->label('الحد الأدنى لقيمة المشروع')
                                            ->numeric()
                                            ->prefix('د.أ'),
                                    ]),
                                    
                                Forms\Components\Section::make('تفاصيل المتطلبات')
                                    ->schema([
                                        Forms\Components\Textarea::make('technical_requirements')
                                            ->label('المتطلبات الفنية')
                                            ->rows(3),
                                        Forms\Components\Textarea::make('financial_requirements')
                                            ->label('المتطلبات المالية')
                                            ->rows(3),
                                        Forms\Components\Textarea::make('other_requirements')
                                            ->label('متطلبات أخرى')
                                            ->rows(3),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tender_number')
                    ->label('رقم العطاء')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('name_ar')
                    ->label('اسم العطاء')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->name_ar),
                Tables\Columns\TextColumn::make('owner.name_ar')
                    ->label('المالك')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge(),
                Tables\Columns\TextColumn::make('tender_type')
                    ->label('النوع')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('submission_deadline')
                    ->label('موعد التقديم')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->color(fn ($record) => $record->submission_deadline?->isPast() ? 'danger' : 
                        ($record->submission_deadline?->diffInDays(now()) <= 7 ? 'warning' : 'success')),
                Tables\Columns\TextColumn::make('estimated_value')
                    ->label('القيمة التقديرية')
                    ->numeric(decimalPlaces: 0)
                    ->sortable()
                    ->money('JOD'),
                Tables\Columns\TextColumn::make('result')
                    ->label('النتيجة')
                    ->badge()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(TenderStatus::class)
                    ->multiple(),
                Tables\Filters\SelectFilter::make('tender_type')
                    ->label('النوع')
                    ->options(TenderType::class),
                Tables\Filters\SelectFilter::make('owner_type')
                    ->label('نوع المالك')
                    ->options(OwnerType::class),
                Tables\Filters\SelectFilter::make('result')
                    ->label('النتيجة')
                    ->options(TenderResult::class),
                Tables\Filters\Filter::make('submission_deadline')
                    ->label('موعد التقديم')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('من'),
                        Forms\Components\DatePicker::make('until')
                            ->label('إلى'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('submission_deadline', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('submission_deadline', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('changeStatus')
                        ->label('تغيير الحالة')
                        ->icon('heroicon-o-arrow-path')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->label('الحالة الجديدة')
                                ->options(TenderStatus::class)
                                ->required(),
                        ])
                        ->action(function (Tender $record, array $data) {
                            $record->update(['status' => $data['status']]);
                        }),
                ]),
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
            'index' => Pages\ListTenders::route('/'),
            'create' => Pages\CreateTender::route('/create'),
            'view' => Pages\ViewTender::route('/{record}'),
            'edit' => Pages\EditTender::route('/{record}/edit'),
        ];
    }
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', TenderStatus::NEW)
            ->orWhere('status', TenderStatus::STUDYING)
            ->count() ?: null;
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
