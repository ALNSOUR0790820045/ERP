<?php

namespace App\Filament\Pages;

use App\Enums\BondType;
use App\Enums\OwnerType;
use App\Enums\SubmissionMethod;
use App\Enums\TenderMethod;
use App\Enums\TenderResult;
use App\Enums\TenderStatus;
use App\Enums\TenderType;
use App\Models\Tender;
use App\Traits\HasTenderPermissions;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

class TenderWorkflow extends Page implements HasForms
{
    use InteractsWithForms;
    use HasTenderPermissions;

    protected static ?string $navigationIcon = 'heroicon-o-plus-circle';
    
    protected static ?string $navigationGroup = 'العطاءات والمناقصات';
    
    protected static ?string $navigationLabel = 'رصد عطاء جديد';
    
    protected static ?string $title = 'رصد عطاء جديد';
    
    protected static ?int $navigationSort = 0;

    /**
     * التحقق من صلاحية الوصول للصفحة
     */
    public static function canAccess(): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        
        // السماح لمدير النظام
        if ($user->isSuperAdmin()) return true;
        
        // التحقق من صلاحيات العطاءات
        return $user->hasAnyPermission([
            'tenders.tender.view',
            'tenders.tender.create',
            'tenders.tender.update',
        ]);
    }

    protected static string $view = 'filament.pages.tender-workflow';

    public ?Tender $record = null;
    
    public ?array $data = [];
    
    public bool $showDuplicateWarning = false;
    
    public array $similarTenders = [];
    
    public bool $duplicateCheckConfirmed = false;

    /**
     * تنظيف النص من المسافات الزائدة والأحرف الخاصة للمقارنة
     */
    protected function normalizeText(?string $text): string
    {
        if (!$text) return '';
        // إزالة المسافات الزائدة من البداية والنهاية
        $text = trim($text);
        // تحويل المسافات المتعددة لمسافة واحدة
        $text = preg_replace('/\s+/', ' ', $text);
        // إزالة الشرطات والرموز الخاصة للمقارنة
        $text = preg_replace('/[-_\/\\\\]/', '', $text);
        // تحويل للحروف الصغيرة
        return mb_strtolower($text);
    }

    /**
     * البحث عن عطاءات مشابهة
     */
    protected function findSimilarTenders(array $data): array
    {
        $similar = [];
        $currentId = $this->record?->id;
        
        $query = Tender::query();
        
        if ($currentId) {
            $query->where('id', '!=', $currentId);
        }
        
        $allTenders = $query->get();
        
        $inputRefNumber = $this->normalizeText($data['reference_number'] ?? '');
        $inputCustomerId = $data['customer_id'] ?? null;
        $inputSubmissionDate = $data['submission_deadline'] ?? null;
        $inputName = $this->normalizeText($data['name_ar'] ?? $data['name_en'] ?? '');
        
        foreach ($allTenders as $tender) {
            $matchReasons = [];
            $matchScore = 0;
            
            // مطابقة رقم المناقصة (بعد التنظيف)
            $tenderRefNumber = $this->normalizeText($tender->reference_number);
            if ($inputRefNumber && $tenderRefNumber && $inputRefNumber === $tenderRefNumber) {
                $matchReasons[] = 'رقم المناقصة متطابق';
                $matchScore += 50;
            } elseif ($inputRefNumber && $tenderRefNumber && similar_text($inputRefNumber, $tenderRefNumber) / max(strlen($inputRefNumber), strlen($tenderRefNumber)) > 0.8) {
                $matchReasons[] = 'رقم المناقصة مشابه جداً';
                $matchScore += 30;
            }
            
            // مطابقة الجهة المشترية
            if ($inputCustomerId && $tender->customer_id && $inputCustomerId == $tender->customer_id) {
                $matchReasons[] = 'نفس الجهة المشترية';
                $matchScore += 25;
            }
            
            // مطابقة تاريخ التقديم
            if ($inputSubmissionDate && $tender->submission_deadline) {
                $inputDate = \Carbon\Carbon::parse($inputSubmissionDate)->format('Y-m-d');
                $tenderDate = \Carbon\Carbon::parse($tender->submission_deadline)->format('Y-m-d');
                if ($inputDate === $tenderDate) {
                    $matchReasons[] = 'نفس تاريخ التقديم';
                    $matchScore += 25;
                }
            }
            
            // مطابقة اسم المناقصة
            $tenderName = $this->normalizeText($tender->name_ar ?? $tender->name_en);
            if ($inputName && $tenderName) {
                $similarity = similar_text($inputName, $tenderName, $percent);
                if ($percent > 70) {
                    $matchReasons[] = 'اسم المناقصة مشابه (' . round($percent) . '%)';
                    $matchScore += 20;
                }
            }
            
            // إذا كانت نسبة التطابق أعلى من 40% (رقم + شيء آخر) أو رقم متطابق تماماً
            if ($matchScore >= 40 || ($matchScore >= 50)) {
                $similar[] = [
                    'id' => $tender->id,
                    'reference_number' => $tender->reference_number,
                    'name' => $tender->name_ar ?? $tender->name_en,
                    'customer' => $tender->customer?->company_name ?? 'غير محدد',
                    'submission_deadline' => $tender->submission_deadline?->format('Y-m-d'),
                    'status' => $tender->status?->getLabel() ?? $tender->status,
                    'match_reasons' => $matchReasons,
                    'match_score' => $matchScore,
                ];
            }
        }
        
        // ترتيب حسب نسبة التطابق
        usort($similar, fn($a, $b) => $b['match_score'] <=> $a['match_score']);
        
        return $similar;
    }

    public function mount(): void
    {
        $recordId = request()->query('record');
        
        if ($recordId) {
            $this->record = Tender::findOrFail($recordId);
            $this->form->fill($this->record->toArray());
        } else {
            $this->record = new Tender();
            $this->form->fill([
                'status' => TenderStatus::NEW,
                'tender_type' => TenderType::SMALL_WORKS,
                'tender_method' => TenderMethod::PUBLIC,
                'owner_type' => OwnerType::GOVERNMENT,
                'country' => 'الأردن',
                'validity_period' => 90,
                'performance_bond_percentage' => 10,
                'retention_percentage' => 10,
                'objection_period_days' => 7,
                'objection_fee' => 500,
                'technical_pass_score' => 70,
                'allow_arithmetic_corrections' => true,
                'words_over_numbers_precedence' => true,
                'esmp_required' => true,
                'code_of_conduct_required' => true,
                'anti_corruption_declaration_required' => true,
                'conflict_of_interest_declaration_required' => true,
            ]);
        }
    }

    public function getTitle(): string|Htmlable
    {
        return $this->record && $this->record->exists 
            ? 'إدارة العطاء: ' . $this->record->name_ar
            : 'رصد عطاء جديد';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make([
                    // ========================================
                    // المرحلة 1: الرصد والتسجيل
                    // ========================================
                    Forms\Components\Wizard\Step::make('الرصد والتسجيل')
                        ->icon('heroicon-o-magnifying-glass')
                        ->description('رصد العطاء وتسجيل البيانات الأساسية')
                        ->schema([
                            // ===== 1. البيانات الأساسية =====
                            Forms\Components\Section::make('البيانات الأساسية')
                                ->icon('heroicon-o-clipboard-document-list')
                                ->description('المعلومات الرئيسية للمناقصة')
                                ->columns(4)
                                ->schema([
                                    Forms\Components\TextInput::make('reference_number')
                                        ->label('رقم المناقصة من جهة المالك')
                                        ->helperText('الرقم الرسمي')
                                        ->required()
                                        ->maxLength(50),
                                    Forms\Components\TextInput::make('tender_number')
                                        ->label('الرقم في النظام')
                                        ->disabled()
                                        ->placeholder('تلقائي'),
                                    Forms\Components\Select::make('tender_type')
                                        ->label('نوع المناقصة')
                                        ->options(TenderType::class)
                                        ->required(),
                                    Forms\Components\Select::make('tender_method')
                                        ->label('أسلوب الطرح')
                                        ->options(TenderMethod::class)
                                        ->required(),
                                    Forms\Components\Toggle::make('is_english_tender')
                                        ->label('باللغة الإنجليزية')
                                        ->live()
                                        ->default(false),
                                    Forms\Components\Toggle::make('is_direct_sale')
                                        ->label('بيع مباشر')
                                        ->helperText('بدون وثائق وكفالات')
                                        ->live()
                                        ->default(false),
                                    Forms\Components\Select::make('tender_scope')
                                        ->label('نطاق المناقصة')
                                        ->options([
                                            'local' => 'محلية',
                                            'international' => 'دولية',
                                        ])
                                        ->default('local'),
                                    Forms\Components\Select::make('status')
                                        ->label('الحالة')
                                        ->options(TenderStatus::class)
                                        ->default(TenderStatus::NEW)
                                        ->disabled(),
                                    Forms\Components\TextInput::make('name_ar')
                                        ->label('اسم المناقصة (عربي)')
                                        ->required(fn (Forms\Get $get) => !$get('is_english_tender'))
                                        ->hidden(fn (Forms\Get $get) => $get('is_english_tender'))
                                        ->maxLength(255)
                                        ->columnSpan(2),
                                    Forms\Components\TextInput::make('name_en')
                                        ->label('اسم المناقصة (إنجليزي)')
                                        ->required(fn (Forms\Get $get) => $get('is_english_tender'))
                                        ->visible(fn (Forms\Get $get) => $get('is_english_tender'))
                                        ->maxLength(255)
                                        ->columnSpan(2),
                                    Forms\Components\Textarea::make('description')
                                        ->label('وصف موجز للأشغال المطلوبة')
                                        ->rows(2)
                                        ->columnSpanFull(),
                                ]),

                            // ===== 2. الجهة المشترية =====
                            Forms\Components\Section::make('الجهة المشترية')
                                ->icon('heroicon-o-building-office-2')
                                ->description('صاحبة المناقصة')
                                ->columns(4)
                                ->schema([
                                    Forms\Components\Select::make('owner_type')
                                        ->label('نوع الجهة')
                                        ->options(OwnerType::class)
                                        ->required(),
                                    Forms\Components\Select::make('customer_id')
                                        ->label('اسم الجهة')
                                        ->relationship('customer', 'company_name')
                                        ->searchable()
                                        ->preload()
                                        ->createOptionForm([
                                            Forms\Components\TextInput::make('company_name')
                                                ->label('اسم الجهة/الشركة')
                                                ->required(),
                                            Forms\Components\TextInput::make('phone')
                                                ->label('الهاتف')->tel(),
                                            Forms\Components\TextInput::make('email')
                                                ->label('البريد')->email(),
                                        ])
                                        ->columnSpan(2),
                                    Forms\Components\TextInput::make('owner_phone')
                                        ->label('هاتف التواصل')
                                        ->tel(),
                                    Forms\Components\TextInput::make('owner_email')
                                        ->label('البريد الإلكتروني')
                                        ->email()
                                        ->columnSpan(2),
                                    Forms\Components\TextInput::make('owner_website')
                                        ->label('الموقع الإلكتروني')
                                        ->url()
                                        ->columnSpan(2),
                                ]),

                            // ===== 3. المواعيد الهامة =====
                            Forms\Components\Section::make('المواعيد الهامة')
                                ->icon('heroicon-o-calendar-days')
                                ->description('جميع التواريخ المتعلقة بالمناقصة')
                                ->columns(4)
                                ->schema([
                                    Forms\Components\DatePicker::make('publication_date')
                                        ->label('تاريخ النشر')
                                        ->required()
                                        ->default(now())
                                        ->live(),
                                    Forms\Components\DatePicker::make('documents_sale_start')
                                        ->label('بداية بيع الوثائق')
                                        ->hidden(fn (Forms\Get $get) => $get('is_direct_sale'))
                                        ->minDate(fn (Forms\Get $get) => $get('publication_date') ?: now()),
                                    Forms\Components\DateTimePicker::make('documents_sale_end')
                                        ->label('آخر موعد لشراء الوثائق')
                                        ->hidden(fn (Forms\Get $get) => $get('is_direct_sale'))
                                        ->minDate(fn (Forms\Get $get) => $get('documents_sale_start') ?: now()),
                                    Forms\Components\DateTimePicker::make('questions_deadline')
                                        ->label('آخر موعد للاستفسارات')
                                        ->minDate(fn (Forms\Get $get) => $get('publication_date') ?: now()),
                                    Forms\Components\DateTimePicker::make('site_visit_date')
                                        ->label('موعد زيارة الموقع')
                                        ->minDate(fn (Forms\Get $get) => $get('publication_date') ?: now()),
                                    Forms\Components\DateTimePicker::make('pre_bid_meeting_date')
                                        ->label('اجتماع ما قبل المناقصة'),
                                    Forms\Components\DateTimePicker::make('submission_deadline')
                                        ->label('⚠️ آخر موعد للتقديم')
                                        ->required()
                                        ->live()
                                        ->minDate(fn (Forms\Get $get) => $get('documents_sale_end') ?: now()),
                                    Forms\Components\DateTimePicker::make('opening_date')
                                        ->label('موعد فتح العروض')
                                        ->hidden(fn (Forms\Get $get) => $get('is_direct_sale'))
                                        ->minDate(fn (Forms\Get $get) => $get('submission_deadline') ?: now()),
                                    Forms\Components\TextInput::make('validity_period')
                                        ->label('مدة سريان العروض')
                                        ->numeric()
                                        ->suffix('يوم')
                                        ->default(90),
                                ]),

                            // ===== 4. شراء الوثائق =====
                            Forms\Components\Section::make('شراء وثائق المناقصة')
                                ->icon('heroicon-o-document-text')
                                ->collapsed()
                                ->hidden(fn (Forms\Get $get) => $get('is_direct_sale'))
                                ->columns(3)
                                ->schema([
                                    Forms\Components\TextInput::make('documents_price')
                                        ->label('ثمن الوثائق (غير مستردة)')
                                        ->numeric()
                                        ->suffix('د.أ')
                                        ->required(),
                                    Forms\Components\Select::make('electronic_submission')
                                        ->label('التقديم الإلكتروني')
                                        ->options([
                                            'accepted' => 'مقبول',
                                            'not_accepted' => 'غير مقبول',
                                        ])
                                        ->default('not_accepted'),
                                    Forms\Components\TextInput::make('clarification_address')
                                        ->label('عنوان الاستيضاحات'),
                                    Forms\Components\Repeater::make('required_documents')
                                        ->label('الأوراق المطلوبة لشراء الوثائق')
                                        ->columnSpanFull()
                                        ->collapsed()
                                        ->schema([
                                            Forms\Components\Select::make('document_id')
                                                ->label('المستند')
                                                ->options(function () {
                                                    return \App\Models\Document::query()
                                                        ->select('id', 'document_number', 'title')
                                                        ->orderBy('title')
                                                        ->get()
                                                        ->mapWithKeys(fn ($doc) => [$doc->id => $doc->title]);
                                                })
                                                ->searchable()
                                                ->required(),
                                            Forms\Components\Toggle::make('is_mandatory')
                                                ->label('إلزامي')
                                                ->default(true),
                                            Forms\Components\TextInput::make('custom_requirement')
                                                ->label('ملاحظات'),
                                        ])
                                        ->columns(3)
                                        ->addActionLabel('+ إضافة مستند')
                                        ->defaultItems(0),
                                ]),

                            // ===== 5. تأمين دخول العطاء =====
                            Forms\Components\Section::make('تأمين دخول العطاء (الكفالة)')
                                ->icon('heroicon-o-banknotes')
                                ->collapsed()
                                ->hidden(fn (Forms\Get $get) => $get('is_direct_sale'))
                                ->columns(4)
                                ->schema([
                                    Forms\Components\Select::make('bid_bond_type')
                                        ->label('شكل التأمين')
                                        ->options([
                                            'bank_guarantee' => 'كفالة بنكية',
                                            'certified_check' => 'شيك مصدق',
                                            'guarantee_or_certified' => 'كفالة أو شيك مصدق',
                                            'any' => 'أي شكل',
                                        ])
                                        ->default('guarantee_or_certified'),
                                    Forms\Components\Select::make('bid_bond_calculation')
                                        ->label('طريقة الحساب')
                                        ->options([
                                            'fixed' => 'مبلغ ثابت',
                                            'percentage' => 'نسبة مئوية',
                                        ])
                                        ->live()
                                        ->default('fixed'),
                                    Forms\Components\TextInput::make('bid_bond_amount')
                                        ->label('قيمة التأمين')
                                        ->numeric()
                                        ->suffix('د.أ')
                                        ->visible(fn (Forms\Get $get) => $get('bid_bond_calculation') === 'fixed'),
                                    Forms\Components\TextInput::make('bid_bond_percentage')
                                        ->label('نسبة التأمين')
                                        ->numeric()
                                        ->suffix('%')
                                        ->visible(fn (Forms\Get $get) => $get('bid_bond_calculation') === 'percentage'),
                                    Forms\Components\TextInput::make('bid_bond_validity_days')
                                        ->label('مدة سريان التأمين')
                                        ->numeric()
                                        ->suffix('يوم'),
                                ]),

                            // ===== 6. متطلبات التأهيل =====
                            Forms\Components\Section::make('متطلبات التأهيل والتصنيف')
                                ->icon('heroicon-o-academic-cap')
                                ->collapsed()
                                ->columns(4)
                                ->schema([
                                    Forms\Components\Select::make('classification_field_id')
                                        ->label('المجال')
                                        ->options(\App\Models\ClassificationField::pluck('name_ar', 'id'))
                                        ->searchable(),
                                    Forms\Components\Select::make('classification_specialty_id')
                                        ->label('الاختصاص')
                                        ->options(\App\Models\ClassificationSpecialty::pluck('name_ar', 'id'))
                                        ->searchable(),
                                    Forms\Components\Select::make('classification_category_id')
                                        ->label('الفئة')
                                        ->options(\App\Models\ClassificationCategory::pluck('name_ar', 'id'))
                                        ->searchable(),
                                    Forms\Components\TextInput::make('classification_scope')
                                        ->label('النطاق المالي'),
                                    Forms\Components\TextInput::make('minimum_experience_years')
                                        ->label('الخبرة المطلوبة')
                                        ->numeric()
                                        ->suffix('سنة'),
                                    Forms\Components\TextInput::make('minimum_similar_projects')
                                        ->label('مشاريع مماثلة')
                                        ->numeric(),
                                    Forms\Components\TextInput::make('minimum_project_value')
                                        ->label('الحد الأدنى للقيمة')
                                        ->numeric()
                                        ->prefix('د.أ'),
                                ]),

                            // ===== 7. الموقع الجغرافي =====
                            Forms\Components\Section::make('موقع المشروع')
                                ->icon('heroicon-o-map-pin')
                                ->collapsed()
                                ->columns(4)
                                ->schema([
                                    Forms\Components\Select::make('country')
                                        ->label('الدولة')
                                        ->options([
                                            'الأردن' => 'الأردن',
                                            'السعودية' => 'السعودية',
                                            'الإمارات' => 'الإمارات',
                                            'العراق' => 'العراق',
                                            'فلسطين' => 'فلسطين',
                                        ])
                                        ->default('الأردن')
                                        ->live(),
                                    Forms\Components\Select::make('city')
                                        ->label('المحافظة')
                                        ->options(fn (Forms\Get $get) => match($get('country')) {
                                            'الأردن' => [
                                                'عمان' => 'عمّان', 'إربد' => 'إربد', 'الزرقاء' => 'الزرقاء',
                                                'العقبة' => 'العقبة', 'الكرك' => 'الكرك', 'المفرق' => 'المفرق',
                                            ],
                                            default => [],
                                        })
                                        ->searchable(),
                                    Forms\Components\TextInput::make('project_district')
                                        ->label('المنطقة/الحي'),
                                    Forms\Components\TextInput::make('google_maps_link')
                                        ->label('رابط جوجل ماب')
                                        ->url(),
                                    Forms\Components\Textarea::make('site_address')
                                        ->label('العنوان التفصيلي')
                                        ->rows(2)
                                        ->columnSpanFull(),
                                ]),

                            // ===== 8. عنوان التقديم =====
                            Forms\Components\Section::make('عنوان صندوق العروض')
                                ->icon('heroicon-o-inbox-arrow-down')
                                ->collapsed()
                                ->columns(4)
                                ->schema([
                                    Forms\Components\TextInput::make('submission_city')
                                        ->label('المدينة'),
                                    Forms\Components\TextInput::make('submission_district')
                                        ->label('المنطقة'),
                                    Forms\Components\TextInput::make('submission_street')
                                        ->label('الشارع'),
                                    Forms\Components\TextInput::make('submission_building')
                                        ->label('المبنى/الطابق'),
                                    Forms\Components\TextInput::make('submission_box_number')
                                        ->label('رقم الصندوق'),
                                    Forms\Components\Textarea::make('submission_notes')
                                        ->label('ملاحظات')
                                        ->rows(2)
                                        ->columnSpan(3),
                                ]),

                            // ===== 9. تجزئة المناقصة =====
                            Forms\Components\Section::make('تجزئة المناقصة (الحزم)')
                                ->icon('heroicon-o-squares-plus')
                                ->collapsed()
                                ->columns(3)
                                ->schema([
                                    Forms\Components\Toggle::make('is_package_tender')
                                        ->label('مناقصة مجزأة')
                                        ->live(),
                                    Forms\Components\TextInput::make('package_count')
                                        ->label('عدد الحزم')
                                        ->numeric()
                                        ->visible(fn (Forms\Get $get) => $get('is_package_tender')),
                                    Forms\Components\Select::make('award_basis')
                                        ->label('أساس الإحالة')
                                        ->options([
                                            'package' => 'حسب الحزمة',
                                            'total' => 'المجموع الكلي',
                                        ])
                                        ->visible(fn (Forms\Get $get) => $get('is_package_tender')),
                                ]),

                            // ===== 10. التمويل =====
                            Forms\Components\Section::make('مصدر التمويل')
                                ->icon('heroicon-o-currency-dollar')
                                ->collapsed()
                                ->columns(3)
                                ->schema([
                                    Forms\Components\Select::make('funding_source')
                                        ->label('مصدر التمويل')
                                        ->options([
                                            'government_budget' => 'الموازنة العامة',
                                            'funded_project' => 'مشروع ممول',
                                            'loan' => 'قرض',
                                            'grant' => 'منحة',
                                        ])
                                        ->default('government_budget')
                                        ->live(),
                                    Forms\Components\TextInput::make('funder_name')
                                        ->label('اسم الممول')
                                        ->visible(fn (Forms\Get $get) => $get('funding_source') !== 'government_budget')
                                        ->columnSpan(2),
                                ]),

                            // ===== 11. ملاحظات =====
                            Forms\Components\Section::make('ملاحظات إضافية')
                                ->icon('heroicon-o-pencil-square')
                                ->collapsed()
                                ->schema([
                                    Forms\Components\Textarea::make('additional_notes')
                                        ->label('')
                                        ->rows(4)
                                        ->placeholder('أي ملاحظات إضافية...'),
                                ]),
                        ]),

                    // ========================================
                    // المرحلة 2: الدراسة والقرار
                    // ========================================
                    Forms\Components\Wizard\Step::make('الدراسة والقرار')
                        ->icon('heroicon-o-clipboard-document-check')
                        ->description('دراسة العطاء واتخاذ قرار المشاركة')
                        ->visible(fn () => $this->record && $this->record->exists && $this->record->status !== TenderStatus::NEW)
                        ->schema([
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

                            Forms\Components\Section::make('قرار المشاركة')
                                ->columns(2)
                                ->schema([
                                    Forms\Components\Select::make('decision')
                                        ->label('القرار')
                                        ->options([
                                            'go' => '✅ Go - المشاركة',
                                            'no_go' => '❌ No-Go - عدم المشاركة',
                                        ])
                                        ->live(),
                                    Forms\Components\DatePicker::make('decision_date')
                                        ->label('تاريخ القرار'),
                                    Forms\Components\Textarea::make('decision_notes')
                                        ->label('مبررات القرار')
                                        ->rows(3)
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    // ========================================
                    // المرحلة 3: إعداد العرض
                    // ========================================
                    Forms\Components\Wizard\Step::make('إعداد العرض')
                        ->icon('heroicon-o-document-text')
                        ->description('إعداد العرض الفني والمالي')
                        ->visible(fn () => $this->record && $this->record->exists && !in_array($this->record->status, [TenderStatus::NEW, TenderStatus::STUDYING]))
                        ->schema([
                            Forms\Components\Section::make('ملخص التسعير')
                                ->columns(5)
                                ->schema([
                                    Forms\Components\TextInput::make('total_direct_cost')
                                        ->label('التكاليف المباشرة')
                                        ->numeric()
                                        ->prefix('د.أ'),
                                    Forms\Components\TextInput::make('total_overhead')
                                        ->label('المصاريف العمومية')
                                        ->numeric()
                                        ->prefix('د.أ'),
                                    Forms\Components\TextInput::make('total_cost')
                                        ->label('إجمالي التكلفة')
                                        ->numeric()
                                        ->prefix('د.أ')
                                        ->disabled(),
                                    Forms\Components\TextInput::make('markup_percentage')
                                        ->label('نسبة الربح %')
                                        ->numeric()
                                        ->suffix('%'),
                                    Forms\Components\TextInput::make('submitted_price')
                                        ->label('السعر المقدم')
                                        ->numeric()
                                        ->prefix('د.أ'),
                                ]),

                            Forms\Components\Section::make('معايير التقييم')
                                ->columns(3)
                                ->schema([
                                    Forms\Components\TextInput::make('technical_pass_score')
                                        ->label('درجة النجاح الفني (%)')
                                        ->numeric()
                                        ->default(70)
                                        ->suffix('%'),
                                    Forms\Components\TextInput::make('technical_weight')
                                        ->label('وزن التقييم الفني (%)')
                                        ->numeric()
                                        ->suffix('%'),
                                    Forms\Components\TextInput::make('financial_weight')
                                        ->label('وزن التقييم المالي (%)')
                                        ->numeric()
                                        ->suffix('%'),
                                ]),

                            Forms\Components\Section::make('التصحيحات الحسابية')
                                ->columns(2)
                                ->schema([
                                    Forms\Components\Toggle::make('allow_arithmetic_corrections')
                                        ->label('السماح بالتصحيحات الحسابية')
                                        ->default(true),
                                    Forms\Components\Toggle::make('words_over_numbers_precedence')
                                        ->label('أولوية الكلمات على الأرقام')
                                        ->default(true),
                                ]),

                            Forms\Components\Section::make('الضمانات الأخرى')
                                ->columns(3)
                                ->collapsed()
                                ->hidden(fn (Forms\Get $get) => $get('is_direct_sale'))
                                ->schema([
                                    Forms\Components\TextInput::make('performance_bond_percentage')
                                        ->label('نسبة ضمان حسن التنفيذ')
                                        ->numeric()
                                        ->default(10)
                                        ->suffix('%'),
                                    Forms\Components\TextInput::make('advance_payment_percentage')
                                        ->label('نسبة الدفعة المقدمة')
                                        ->numeric()
                                        ->suffix('%'),
                                    Forms\Components\TextInput::make('retention_percentage')
                                        ->label('نسبة المحتجزات')
                                        ->numeric()
                                        ->default(10)
                                        ->suffix('%'),
                                ]),
                        ]),

                    // ========================================
                    // المرحلة 4: المتطلبات الأردنية
                    // ========================================
                    Forms\Components\Wizard\Step::make('المتطلبات الأردنية')
                        ->icon('heroicon-o-flag')
                        ->description('الأفضليات والإقرارات والائتلافات')
                        ->visible(fn () => $this->record && $this->record->exists && !in_array($this->record->status, [TenderStatus::NEW, TenderStatus::STUDYING]))
                        ->schema([
                            Forms\Components\Section::make('فترة الاعتراض')
                                ->columns(4)
                                ->schema([
                                    Forms\Components\TextInput::make('objection_period_days')
                                        ->label('مدة فترة الاعتراض (يوم)')
                                        ->numeric()
                                        ->default(7),
                                    Forms\Components\DatePicker::make('objection_period_start')
                                        ->label('بداية فترة الاعتراض'),
                                    Forms\Components\DatePicker::make('objection_period_end')
                                        ->label('نهاية فترة الاعتراض'),
                                    Forms\Components\TextInput::make('objection_fee')
                                        ->label('رسم الاعتراض (د.أ)')
                                        ->numeric()
                                        ->default(500),
                                ]),

                            Forms\Components\Section::make('اجتماع ما قبل تقديم العطاءات')
                                ->columns(3)
                                ->schema([
                                    Forms\Components\Toggle::make('pre_bid_meeting_required')
                                        ->label('اجتماع مطلوب')
                                        ->live(),
                                    Forms\Components\DateTimePicker::make('pre_bid_meeting_date')
                                        ->label('موعد الاجتماع')
                                        ->visible(fn (Forms\Get $get) => $get('pre_bid_meeting_required')),
                                    Forms\Components\TextInput::make('pre_bid_meeting_location')
                                        ->label('مكان الاجتماع')
                                        ->visible(fn (Forms\Get $get) => $get('pre_bid_meeting_required')),
                                    Forms\Components\Textarea::make('pre_bid_meeting_minutes')
                                        ->label('محضر الاجتماع')
                                        ->rows(3)
                                        ->columnSpanFull()
                                        ->visible(fn (Forms\Get $get) => $get('pre_bid_meeting_required')),
                                ]),

                            Forms\Components\Section::make('الأفضليات السعرية')
                                ->columns(3)
                                ->schema([
                                    Forms\Components\Toggle::make('allows_price_preferences')
                                        ->label('تسمح بالأفضليات السعرية')
                                        ->live(),
                                    Forms\Components\TextInput::make('sme_preference_percentage')
                                        ->label('نسبة أفضلية SME (%)')
                                        ->numeric()
                                        ->default(5)
                                        ->suffix('%')
                                        ->visible(fn (Forms\Get $get) => $get('allows_price_preferences')),
                                    Forms\Components\Toggle::make('local_products_preference')
                                        ->label('أفضلية للمنتجات المحلية')
                                        ->visible(fn (Forms\Get $get) => $get('allows_price_preferences')),
                                ]),

                            Forms\Components\Section::make('المقاولين الفرعيين')
                                ->columns(3)
                                ->schema([
                                    Forms\Components\Toggle::make('allows_subcontracting')
                                        ->label('يسمح بالتعاقد الفرعي')
                                        ->live(),
                                    Forms\Components\TextInput::make('max_subcontracting_percentage')
                                        ->label('الحد الأقصى للتعاقد الفرعي (%)')
                                        ->numeric()
                                        ->default(33)
                                        ->suffix('%')
                                        ->visible(fn (Forms\Get $get) => $get('allows_subcontracting')),
                                    Forms\Components\TextInput::make('local_subcontractor_percentage')
                                        ->label('الحد الأدنى للمحليين (%)')
                                        ->numeric()
                                        ->default(10)
                                        ->suffix('%')
                                        ->visible(fn (Forms\Get $get) => $get('allows_subcontracting')),
                                ]),

                            Forms\Components\Section::make('الائتلافات')
                                ->columns(2)
                                ->schema([
                                    Forms\Components\Toggle::make('allows_consortium')
                                        ->label('يسمح بالائتلافات')
                                        ->live(),
                                    Forms\Components\TextInput::make('max_consortium_members')
                                        ->label('الحد الأقصى لأعضاء الائتلاف')
                                        ->numeric()
                                        ->visible(fn (Forms\Get $get) => $get('allows_consortium')),
                                ]),

                            Forms\Components\Section::make('الإقرارات المطلوبة')
                                ->columns(2)
                                ->schema([
                                    Forms\Components\Toggle::make('esmp_required')
                                        ->label('خطة الإدارة البيئية والاجتماعية (ESMP)')
                                        ->default(true),
                                    Forms\Components\Toggle::make('code_of_conduct_required')
                                        ->label('قواعد السلوك')
                                        ->default(true),
                                    Forms\Components\Toggle::make('anti_corruption_declaration_required')
                                        ->label('إقرار مكافحة الفساد')
                                        ->default(true),
                                    Forms\Components\Toggle::make('conflict_of_interest_declaration_required')
                                        ->label('إقرار عدم تضارب المصالح')
                                        ->default(true),
                                ]),
                        ]),

                    // ========================================
                    // المرحلة 5: التقديم
                    // ========================================
                    Forms\Components\Wizard\Step::make('التقديم')
                        ->icon('heroicon-o-paper-airplane')
                        ->description('تقديم العطاء وتسجيل الإيصال')
                        ->visible(fn () => $this->record && $this->record->exists && !in_array($this->record->status, [TenderStatus::NEW, TenderStatus::STUDYING]))
                        ->schema([
                            Forms\Components\Section::make('بيانات التقديم')
                                ->columns(2)
                                ->schema([
                                    Forms\Components\DateTimePicker::make('submission_date')
                                        ->label('تاريخ ووقت التقديم'),
                                    Forms\Components\Select::make('submission_method')
                                        ->label('طريقة التقديم')
                                        ->options(SubmissionMethod::class),
                                    Forms\Components\TextInput::make('receipt_number')
                                        ->label('رقم الإيصال/المرجع'),
                                    Forms\Components\TextInput::make('submitted_price')
                                        ->label('السعر النهائي المقدم')
                                        ->numeric()
                                        ->prefix('د.أ'),
                                ]),
                        ]),

                    // ========================================
                    // المرحلة 6: الفتح والنتائج
                    // ========================================
                    Forms\Components\Wizard\Step::make('الفتح والنتائج')
                        ->icon('heroicon-o-trophy')
                        ->description('نتائج الفتح والترسية')
                        ->visible(fn () => $this->record && $this->record->exists && !in_array($this->record->status, [TenderStatus::NEW, TenderStatus::STUDYING]))
                        ->schema([
                            Forms\Components\Section::make('نتائج الفتح')
                                ->columns(2)
                                ->schema([
                                    Forms\Components\TextInput::make('our_rank')
                                        ->label('ترتيبنا')
                                        ->numeric(),
                                ]),

                            Forms\Components\Section::make('النتيجة النهائية')
                                ->columns(2)
                                ->schema([
                                    Forms\Components\Select::make('result')
                                        ->label('النتيجة')
                                        ->options(TenderResult::class)
                                        ->live(),
                                    Forms\Components\DatePicker::make('award_date')
                                        ->label('تاريخ الترسية'),
                                    Forms\Components\TextInput::make('winner_name')
                                        ->label('اسم الفائز')
                                        ->visible(fn (Forms\Get $get) => $get('result') === 'lost'),
                                    Forms\Components\TextInput::make('winning_price')
                                        ->label('السعر الفائز')
                                        ->numeric()
                                        ->prefix('د.أ'),
                                    Forms\Components\Textarea::make('loss_reason')
                                        ->label('سبب الخسارة')
                                        ->rows(2)
                                        ->visible(fn (Forms\Get $get) => $get('result') === 'lost')
                                        ->columnSpanFull(),
                                    Forms\Components\Textarea::make('lessons_learned')
                                        ->label('الدروس المستفادة')
                                        ->rows(3)
                                        ->columnSpanFull(),
                                ]),
                        ]),
                ])
                ->skippable()
                ->persistStepInQueryString()
                ->submitAction(view('filament.pages.tender-workflow-submit'))
                ->columnSpanFull(),
            ])
            ->statePath('data')
            ->model($this->record);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        
        // فحص التكرار فقط للعطاءات الجديدة أو إذا لم يتم التأكيد
        if (!$this->duplicateCheckConfirmed && !($this->record && $this->record->exists)) {
            $this->similarTenders = $this->findSimilarTenders($data);
            
            if (!empty($this->similarTenders)) {
                $this->showDuplicateWarning = true;
                return;
            }
        }
        
        $this->performSave($data);
    }
    
    public function confirmSaveAnyway(): void
    {
        $this->duplicateCheckConfirmed = true;
        $this->showDuplicateWarning = false;
        $data = $this->form->getState();
        $this->performSave($data);
    }
    
    public function cancelDuplicateSave(): void
    {
        $this->showDuplicateWarning = false;
        $this->similarTenders = [];
    }
    
    protected function performSave(array $data): void
    {
        $data['status'] = TenderStatus::NEW; // حفظ كمسودة
        
        if ($this->record && $this->record->exists) {
            $this->record->update($data);
            $message = 'تم حفظ المسودة بنجاح';
        } else {
            $this->record = Tender::create($data);
            $message = 'تم حفظ المسودة بنجاح';
        }
        
        $this->form->model($this->record)->fill($this->record->toArray());
        
        // إعادة تعيين متغيرات التكرار
        $this->duplicateCheckConfirmed = false;
        $this->similarTenders = [];
        
        Notification::make()
            ->title($message)
            ->icon('heroicon-o-document')
            ->success()
            ->send();
            
        // Redirect to same page with record
        $this->redirect(static::getUrl() . '?record=' . $this->record->id);
    }

    public function sendForStudy(): void
    {
        $data = $this->form->getState();
        
        // فحص التكرار فقط للعطاءات الجديدة
        if (!$this->duplicateCheckConfirmed && !($this->record && $this->record->exists)) {
            $this->similarTenders = $this->findSimilarTenders($data);
            
            if (!empty($this->similarTenders)) {
                $this->showDuplicateWarning = true;
                return;
            }
        }
        
        $this->performSendForStudy($data);
    }
    
    public function confirmSendForStudyAnyway(): void
    {
        $this->duplicateCheckConfirmed = true;
        $this->showDuplicateWarning = false;
        $data = $this->form->getState();
        $this->performSendForStudy($data);
    }
    
    protected function performSendForStudy(array $data): void
    {
        $data['status'] = TenderStatus::STUDYING; // تغيير الحالة للدراسة
        
        if ($this->record && $this->record->exists) {
            $this->record->update($data);
        } else {
            $this->record = Tender::create($data);
        }
        
        $this->form->model($this->record)->fill($this->record->toArray());
        
        // إعادة تعيين متغيرات التكرار
        $this->duplicateCheckConfirmed = false;
        $this->similarTenders = [];
        
        Notification::make()
            ->title('تم إرسال العطاء للدراسة والقرار')
            ->icon('heroicon-o-paper-airplane')
            ->success()
            ->send();
            
        // Redirect to same page with record
        $this->redirect(static::getUrl() . '?record=' . $this->record->id . '&step=aldrast-walqrar');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back_to_list')
                ->label('العودة للقائمة')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url('/admin/tenders'),
        ];
    }
}
