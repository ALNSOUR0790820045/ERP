<?php

namespace App\Filament\Resources;

use App\Enums\ContractStatus;
use App\Enums\ContractType;
use App\Enums\FidicType;
use App\Enums\PricingMethod;
use App\Filament\Resources\ContractResource\Pages;
use App\Filament\Resources\ContractResource\RelationManagers;
use App\Models\Contract;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ContractResource extends Resource
{
    protected static ?string $model = Contract::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    protected static ?string $navigationGroup = 'العقود';
    
    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'عقد';
    
    protected static ?string $pluralModelLabel = 'العقود';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('contract_tabs')
                    ->tabs([
                        // Tab 1: البيانات الأساسية
                        Forms\Components\Tabs\Tab::make('البيانات الأساسية')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Forms\Components\Section::make('معلومات العقد')
                                    ->columns(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('contract_number')
                                            ->label('رقم العقد')
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(50),
                                            
                                        Forms\Components\Select::make('contract_type')
                                            ->label('نوع العقد')
                                            ->options(ContractType::class)
                                            ->required(),
                                            
                                        Forms\Components\Select::make('fidic_type')
                                            ->label('نوع FIDIC')
                                            ->options(FidicType::class),
                                            
                                        Forms\Components\TextInput::make('name_ar')
                                            ->label('اسم العقد (عربي)')
                                            ->required()
                                            ->maxLength(500)
                                            ->columnSpan(2),
                                            
                                        Forms\Components\Select::make('pricing_method')
                                            ->label('طريقة التسعير')
                                            ->options(PricingMethod::class)
                                            ->required(),
                                            
                                        Forms\Components\TextInput::make('name_en')
                                            ->label('اسم العقد (إنجليزي)')
                                            ->maxLength(500)
                                            ->columnSpan(2),
                                            
                                        Forms\Components\Select::make('status')
                                            ->label('الحالة')
                                            ->options(ContractStatus::class)
                                            ->default(ContractStatus::DRAFT),
                                            
                                        Forms\Components\Textarea::make('description')
                                            ->label('وصف العقد')
                                            ->rows(2)
                                            ->columnSpanFull(),
                                            
                                        Forms\Components\Textarea::make('scope_of_work')
                                            ->label('نطاق العمل')
                                            ->rows(3)
                                            ->columnSpanFull(),
                                    ]),
                                    
                                Forms\Components\Section::make('الربط')
                                    ->columns(3)
                                    ->schema([
                                        Forms\Components\Select::make('tender_id')
                                            ->label('المناقصة')
                                            ->relationship('tender', 'name_ar')
                                            ->searchable()
                                            ->preload(),
                                            
                                        Forms\Components\Select::make('company_id')
                                            ->label('الشركة')
                                            ->relationship('company', 'name_ar')
                                            ->searchable()
                                            ->preload(),
                                    ]),
                            ]),
                            
                        // Tab 2: أطراف العقد
                        Forms\Components\Tabs\Tab::make('أطراف العقد')
                            ->icon('heroicon-o-user-group')
                            ->schema([
                                Forms\Components\Section::make('صاحب العمل')
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\Select::make('employer_id')
                                            ->label('صاحب العمل')
                                            ->relationship('employer', 'name_ar')
                                            ->searchable()
                                            ->preload(),
                                            
                                        Forms\Components\TextInput::make('employer_name')
                                            ->label('اسم صاحب العمل')
                                            ->maxLength(255),
                                            
                                        Forms\Components\TextInput::make('employer_representative')
                                            ->label('ممثل صاحب العمل'),
                                            
                                        Forms\Components\TextInput::make('employer_contact')
                                            ->label('جهة الاتصال'),
                                    ]),
                                    
                                Forms\Components\Section::make('المهندس / الاستشاري')
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\Select::make('engineer_id')
                                            ->label('الاستشاري')
                                            ->relationship('engineer', 'name_ar')
                                            ->searchable()
                                            ->preload(),
                                            
                                        Forms\Components\TextInput::make('engineer_name')
                                            ->label('اسم المهندس'),
                                            
                                        Forms\Components\TextInput::make('engineer_representative')
                                            ->label('ممثل المهندس'),
                                    ]),
                                    
                                Forms\Components\Section::make('المقاول')
                                    ->columns(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('contractor_name')
                                            ->label('اسم المقاول'),
                                            
                                        Forms\Components\TextInput::make('contractor_representative')
                                            ->label('ممثل المقاول'),
                                            
                                        Forms\Components\TextInput::make('site_manager')
                                            ->label('مدير الموقع'),
                                    ]),
                            ]),
                            
                        // Tab 3: التواريخ والمدد
                        Forms\Components\Tabs\Tab::make('التواريخ والمدد')
                            ->icon('heroicon-o-calendar')
                            ->schema([
                                Forms\Components\Section::make('تواريخ العقد')
                                    ->columns(3)
                                    ->schema([
                                        Forms\Components\DatePicker::make('award_date')
                                            ->label('تاريخ الترسية'),
                                            
                                        Forms\Components\DatePicker::make('signing_date')
                                            ->label('تاريخ التوقيع'),
                                            
                                        Forms\Components\DatePicker::make('commencement_date')
                                            ->label('تاريخ البدء'),
                                            
                                        Forms\Components\DatePicker::make('original_completion_date')
                                            ->label('تاريخ الإنجاز الأصلي'),
                                            
                                        Forms\Components\DatePicker::make('current_completion_date')
                                            ->label('تاريخ الإنجاز الحالي'),
                                            
                                        Forms\Components\TextInput::make('defects_liability_months')
                                            ->label('فترة ضمان العيوب (شهر)')
                                            ->numeric()
                                            ->default(12),
                                    ]),
                                    
                                Forms\Components\Section::make('المدد')
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('original_duration_days')
                                            ->label('المدة الأصلية (يوم)')
                                            ->numeric(),
                                            
                                        Forms\Components\TextInput::make('current_duration_days')
                                            ->label('المدة الحالية (يوم)')
                                            ->numeric(),
                                    ]),
                                    
                                Forms\Components\Section::make('تواريخ الاستلام')
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\DatePicker::make('provisional_acceptance_date')
                                            ->label('تاريخ الاستلام الابتدائي'),
                                            
                                        Forms\Components\DatePicker::make('final_acceptance_date')
                                            ->label('تاريخ الاستلام النهائي'),
                                    ]),
                            ]),
                            
                        // Tab 4: القيم المالية
                        Forms\Components\Tabs\Tab::make('القيم المالية')
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                Forms\Components\Section::make('قيمة العقد')
                                    ->columns(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('original_value')
                                            ->label('القيمة الأصلية')
                                            ->numeric()
                                            ->default(0),
                                            
                                        Forms\Components\TextInput::make('current_value')
                                            ->label('القيمة الحالية')
                                            ->numeric()
                                            ->default(0),
                                            
                                        Forms\Components\Select::make('currency_id')
                                            ->label('العملة')
                                            ->relationship('currency', 'code')
                                            ->searchable()
                                            ->preload(),
                                            
                                        Forms\Components\TextInput::make('exchange_rate')
                                            ->label('سعر الصرف')
                                            ->numeric()
                                            ->default(1),
                                            
                                        Forms\Components\TextInput::make('vat_percentage')
                                            ->label('نسبة الضريبة %')
                                            ->numeric()
                                            ->default(0),
                                            
                                        Forms\Components\Toggle::make('vat_included')
                                            ->label('الضريبة مشمولة'),
                                    ]),
                                    
                                Forms\Components\Section::make('الدفعة المقدمة')
                                    ->columns(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('advance_payment_percentage')
                                            ->label('نسبة الدفعة المقدمة %')
                                            ->numeric(),
                                            
                                        Forms\Components\TextInput::make('advance_payment_amount')
                                            ->label('مبلغ الدفعة المقدمة')
                                            ->numeric(),
                                            
                                        Forms\Components\Select::make('advance_recovery_method')
                                            ->label('طريقة الاستقطاع')
                                            ->options([
                                                'fixed' => 'نسبة ثابتة',
                                                'proportional' => 'نسبي',
                                            ]),
                                            
                                        Forms\Components\TextInput::make('advance_recovery_start')
                                            ->label('بداية الاستقطاع %')
                                            ->numeric(),
                                            
                                        Forms\Components\TextInput::make('advance_recovery_rate')
                                            ->label('نسبة الاستقطاع %')
                                            ->numeric(),
                                    ]),
                                    
                                Forms\Components\Section::make('المحتجزات')
                                    ->columns(4)
                                    ->schema([
                                        Forms\Components\TextInput::make('retention_percentage')
                                            ->label('نسبة المحتجز %')
                                            ->numeric()
                                            ->default(10),
                                            
                                        Forms\Components\TextInput::make('retention_limit_percentage')
                                            ->label('حد المحتجز %')
                                            ->numeric()
                                            ->default(5),
                                            
                                        Forms\Components\TextInput::make('first_retention_release')
                                            ->label('الإفراج الأول %')
                                            ->numeric()
                                            ->default(50),
                                            
                                        Forms\Components\TextInput::make('final_retention_release')
                                            ->label('الإفراج النهائي %')
                                            ->numeric()
                                            ->default(50),
                                    ]),
                                    
                                Forms\Components\Section::make('شروط الدفع')
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('payment_terms_days')
                                            ->label('مدة السداد (يوم)')
                                            ->numeric()
                                            ->default(30),
                                            
                                        Forms\Components\Select::make('billing_cycle')
                                            ->label('دورة الفوترة')
                                            ->options([
                                                'monthly' => 'شهري',
                                                'bi_weekly' => 'نصف شهري',
                                                'milestone' => 'حسب المراحل',
                                            ])
                                            ->default('monthly'),
                                    ]),
                            ]),
                            
                        // Tab 5: الضمانات
                        Forms\Components\Tabs\Tab::make('الضمانات والتأمينات')
                            ->icon('heroicon-o-shield-check')
                            ->schema([
                                Forms\Components\Section::make('كفالة حسن التنفيذ')
                                    ->columns(4)
                                    ->schema([
                                        Forms\Components\TextInput::make('performance_bond_percentage')
                                            ->label('النسبة %')
                                            ->numeric()
                                            ->default(10),
                                            
                                        Forms\Components\TextInput::make('performance_bond_amount')
                                            ->label('المبلغ')
                                            ->numeric(),
                                            
                                        Forms\Components\Select::make('performance_bond_type')
                                            ->label('النوع')
                                            ->options([
                                                'bank_guarantee' => 'كفالة بنكية',
                                                'insurance' => 'تأمين',
                                                'cash' => 'نقدي',
                                            ]),
                                            
                                        Forms\Components\DatePicker::make('performance_bond_validity')
                                            ->label('تاريخ الصلاحية'),
                                    ]),
                                    
                                Forms\Components\Section::make('كفالة الدفعة المقدمة')
                                    ->columns(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('advance_bond_percentage')
                                            ->label('النسبة %')
                                            ->numeric(),
                                            
                                        Forms\Components\TextInput::make('advance_bond_amount')
                                            ->label('المبلغ')
                                            ->numeric(),
                                            
                                        Forms\Components\DatePicker::make('advance_bond_validity')
                                            ->label('تاريخ الصلاحية'),
                                    ]),
                                    
                                Forms\Components\Section::make('التأمينات')
                                    ->columns(3)
                                    ->schema([
                                        Forms\Components\Toggle::make('car_insurance_required')
                                            ->label('تأمين جميع أخطار المقاولين')
                                            ->default(true),
                                            
                                        Forms\Components\Toggle::make('third_party_insurance')
                                            ->label('تأمين الطرف الثالث')
                                            ->default(true),
                                            
                                        Forms\Components\Toggle::make('professional_liability')
                                            ->label('المسؤولية المهنية'),
                                    ]),
                            ]),
                            
                        // Tab 6: الشروط الخاصة
                        Forms\Components\Tabs\Tab::make('الشروط الخاصة')
                            ->icon('heroicon-o-clipboard-document-list')
                            ->schema([
                                Forms\Components\Section::make('غرامات التأخير')
                                    ->columns(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('liquidated_damages_rate')
                                            ->label('معدل الغرامة اليومي')
                                            ->numeric()
                                            ->suffix('%'),
                                            
                                        Forms\Components\TextInput::make('liquidated_damages_max')
                                            ->label('الحد الأقصى للغرامات')
                                            ->numeric()
                                            ->suffix('%'),
                                            
                                        Forms\Components\TextInput::make('bonus_rate')
                                            ->label('معدل المكافأة')
                                            ->numeric()
                                            ->suffix('%'),
                                    ]),
                                    
                                Forms\Components\Section::make('تعديل الأسعار')
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\Toggle::make('price_adjustment_applicable')
                                            ->label('تعديل الأسعار مطبق')
                                            ->columnSpanFull(),
                                            
                                        Forms\Components\DatePicker::make('base_date')
                                            ->label('تاريخ الأساس'),
                                            
                                        Forms\Components\TextInput::make('threshold_percentage')
                                            ->label('نسبة الحد الأدنى %')
                                            ->numeric(),
                                            
                                        Forms\Components\Textarea::make('price_adjustment_formula')
                                            ->label('معادلة التعديل')
                                            ->columnSpanFull(),
                                    ]),
                                    
                                Forms\Components\Section::make('فض النزاعات')
                                    ->columns(3)
                                    ->schema([
                                        Forms\Components\Select::make('dispute_resolution')
                                            ->label('آلية فض النزاعات')
                                            ->options([
                                                'arbitration' => 'تحكيم',
                                                'court' => 'محكمة',
                                                'mediation' => 'وساطة',
                                            ]),
                                            
                                        Forms\Components\TextInput::make('governing_law')
                                            ->label('القانون المطبق'),
                                            
                                        Forms\Components\TextInput::make('arbitration_rules')
                                            ->label('قواعد التحكيم'),
                                    ]),
                                    
                                Forms\Components\Section::make('شروط إضافية')
                                    ->schema([
                                        Forms\Components\Textarea::make('force_majeure_clause')
                                            ->label('شرط القوة القاهرة')
                                            ->rows(2),
                                            
                                        Forms\Components\Textarea::make('special_conditions')
                                            ->label('شروط خاصة أخرى')
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
                Tables\Columns\TextColumn::make('contract_number')
                    ->label('رقم العقد')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('name_ar')
                    ->label('اسم العقد')
                    ->searchable()
                    ->limit(40)
                    ->wrap(),
                    
                Tables\Columns\TextColumn::make('contract_type')
                    ->label('النوع')
                    ->badge(),
                    
                Tables\Columns\TextColumn::make('employer.name_ar')
                    ->label('صاحب العمل')
                    ->searchable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('original_value')
                    ->label('القيمة الأصلية')
                    ->money('JOD')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('current_value')
                    ->label('القيمة الحالية')
                    ->money('JOD')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('commencement_date')
                    ->label('تاريخ البدء')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('current_completion_date')
                    ->label('تاريخ الإنجاز')
                    ->date()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('completion_percentage')
                    ->label('الإنجاز %')
                    ->suffix('%')
                    ->color(fn ($state) => $state >= 100 ? 'success' : ($state >= 50 ? 'warning' : 'danger')),
                    
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(ContractStatus::class),
                    
                Tables\Filters\SelectFilter::make('contract_type')
                    ->label('نوع العقد')
                    ->options(ContractType::class),
                    
                Tables\Filters\SelectFilter::make('employer_id')
                    ->label('صاحب العمل')
                    ->relationship('employer', 'name_ar'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ItemsRelationManager::class,
            RelationManagers\VariationsRelationManager::class,
            RelationManagers\BondsRelationManager::class,
            RelationManagers\ClaimsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContracts::route('/'),
            'create' => Pages\CreateContract::route('/create'),
            'view' => Pages\ViewContract::route('/{record}'),
            'edit' => Pages\EditContract::route('/{record}/edit'),
        ];
    }
}
