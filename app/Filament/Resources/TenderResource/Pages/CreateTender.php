<?php

namespace App\Filament\Resources\TenderResource\Pages;

use App\Enums\OwnerType;
use App\Enums\TenderMethod;
use App\Enums\TenderStatus;
use App\Enums\TenderType;
use App\Filament\Resources\TenderResource;
use App\Models\Tender;
use App\Models\Document;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

/**
 * صفحة إنشاء عطاء جديد (الرصد)
 * الصلاحية المطلوبة: tenders.tender.create
 * 
 * تشمل:
 * - كشف التكرار
 * - تدقيق التواريخ
 * - المستندات المطلوبة
 * - الكفالات
 * - صورة الإعلان
 * - إرسال للدراسة
 */
class CreateTender extends CreateRecord
{
    protected static string $resource = TenderResource::class;

    protected static ?string $title = 'رصد عطاء جديد';

    public bool $showDuplicateWarning = false;
    public array $similarTenders = [];
    public bool $duplicateCheckConfirmed = false;

    /**
     * التحقق من صلاحية الإنشاء
     */
    public static function canAccess(array $parameters = []): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        
        if ($user->isSuperAdmin()) return true;
        
        return $user->hasAnyPermission([
            'tenders.tender.create',
            'tenders.discovery.create',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make([
                    // ========================================
                    // الخطوة 1: البيانات الأساسية
                    // ========================================
                    Forms\Components\Wizard\Step::make('البيانات الأساسية')
                        ->icon('heroicon-o-clipboard-document-list')
                        ->description('المعلومات الرئيسية للمناقصة')
                        ->schema([
                            Forms\Components\Section::make()
                                ->columns(4)
                                ->schema([
                                    Forms\Components\TextInput::make('reference_number')
                                        ->label('رقم المناقصة من جهة المالك')
                                        ->helperText('الرقم الرسمي')
                                        ->required()
                                        ->maxLength(50)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function ($state) {
                                            $this->checkForDuplicates();
                                        }),
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
                                        ->default('local')
                                        ->required(),
                                    Forms\Components\Select::make('priority')
                                        ->label('الأولوية')
                                        ->options([
                                            'high' => 'عالية',
                                            'medium' => 'متوسطة',
                                            'low' => 'منخفضة',
                                        ])
                                        ->default('medium'),
                                    Forms\Components\TextInput::make('name_ar')
                                        ->label('اسم المناقصة (عربي)')
                                        ->required(fn (Forms\Get $get) => !$get('is_english_tender'))
                                        ->hidden(fn (Forms\Get $get) => $get('is_english_tender'))
                                        ->maxLength(255)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function ($state) {
                                            $this->checkForDuplicates();
                                        })
                                        ->columnSpan(2),
                                    Forms\Components\TextInput::make('name_en')
                                        ->label('اسم المناقصة (إنجليزي)')
                                        ->required(fn (Forms\Get $get) => $get('is_english_tender'))
                                        ->visible(fn (Forms\Get $get) => $get('is_english_tender'))
                                        ->maxLength(255)
                                        ->columnSpan(2),
                                    Forms\Components\Textarea::make('description')
                                        ->label('وصف موجز للأشغال المطلوبة')
                                        ->rows(3)
                                        ->columnSpanFull(),
                                ]),
                            
                            // تحذير التكرار
                            Forms\Components\Placeholder::make('duplicate_warning')
                                ->label('')
                                ->content(fn () => $this->showDuplicateWarning 
                                    ? view('filament.components.duplicate-warning', ['tenders' => $this->similarTenders])
                                    : '')
                                ->visible(fn () => $this->showDuplicateWarning),
                        ]),

                    // ========================================
                    // الخطوة 2: الجهة المشترية
                    // ========================================
                    Forms\Components\Wizard\Step::make('الجهة المشترية')
                        ->icon('heroicon-o-building-office-2')
                        ->description('صاحبة المناقصة')
                        ->schema([
                            Forms\Components\Section::make()
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
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function ($state) {
                                            $this->checkForDuplicates();
                                        })
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
                                    Forms\Components\TextInput::make('owner_contact_person')
                                        ->label('جهة الاتصال'),
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
                        ]),

                    // ========================================
                    // الخطوة 3: المواعيد الهامة
                    // ========================================
                    Forms\Components\Wizard\Step::make('المواعيد')
                        ->icon('heroicon-o-calendar-days')
                        ->description('التواريخ مع التحقق التلقائي')
                        ->schema([
                            Forms\Components\Section::make('المواعيد الهامة')
                                ->description('⚠️ يتم التحقق من تسلسل التواريخ تلقائياً')
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
                                        ->minDate(fn (Forms\Get $get) => $get('publication_date') ? \Carbon\Carbon::parse($get('publication_date'))->addDay() : now()->addDay())
                                        ->rule(function (Forms\Get $get) {
                                            return function (string $attribute, $value, \Closure $fail) use ($get) {
                                                if ($value && $get('publication_date')) {
                                                    $pubDate = \Carbon\Carbon::parse($get('publication_date'));
                                                    $subDate = \Carbon\Carbon::parse($value);
                                                    if ($subDate->lte($pubDate)) {
                                                        $fail('تاريخ التقديم يجب أن يكون بعد تاريخ النشر');
                                                    }
                                                }
                                            };
                                        }),
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
                        ]),

                    // ========================================
                    // الخطوة 4: الوثائق والكفالات
                    // ========================================
                    Forms\Components\Wizard\Step::make('الوثائق والكفالات')
                        ->icon('heroicon-o-document-text')
                        ->description('شراء الوثائق والتأمينات')
                        ->visible(fn (Forms\Get $get) => !$get('is_direct_sale'))
                        ->schema([
                            Forms\Components\Section::make('شراء وثائق المناقصة')
                                ->columns(3)
                                ->schema([
                                    Forms\Components\TextInput::make('documents_price')
                                        ->label('ثمن الوثائق (غير مستردة)')
                                        ->numeric()
                                        ->suffix('د.أ'),
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
                                        ->collapsible()
                                        ->schema([
                                            Forms\Components\Select::make('document_id')
                                                ->label('المستند')
                                                ->options(function () {
                                                    return Document::query()
                                                        ->select('id', 'document_number', 'title')
                                                        ->orderBy('title')
                                                        ->get()
                                                        ->mapWithKeys(fn ($doc) => [$doc->id => $doc->title ?? $doc->document_number]);
                                                })
                                                ->searchable(),
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

                            Forms\Components\Section::make('تأمين دخول العطاء (الكفالة)')
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
                        ]),

                    // ========================================
                    // الخطوة 5: التأهيل والموقع
                    // ========================================
                    Forms\Components\Wizard\Step::make('التأهيل والموقع')
                        ->icon('heroicon-o-map-pin')
                        ->description('متطلبات التصنيف والموقع')
                        ->schema([
                            Forms\Components\Section::make('متطلبات التأهيل والتصنيف')
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

                            Forms\Components\Section::make('موقع المشروع')
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
                                                'جرش' => 'جرش', 'عجلون' => 'عجلون', 'مادبا' => 'مادبا',
                                                'البلقاء' => 'البلقاء', 'الطفيلة' => 'الطفيلة', 'معان' => 'معان',
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

                            Forms\Components\Section::make('عنوان صندوق العروض')
                                ->columns(4)
                                ->collapsible()
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
                        ]),

                    // ========================================
                    // الخطوة 6: التمويل والمرفقات
                    // ========================================
                    Forms\Components\Wizard\Step::make('التمويل والمرفقات')
                        ->icon('heroicon-o-currency-dollar')
                        ->description('القيم المالية والملفات')
                        ->schema([
                            Forms\Components\Section::make('القيم المالية')
                                ->columns(4)
                                ->schema([
                                    Forms\Components\TextInput::make('estimated_value')
                                        ->label('القيمة التقديرية')
                                        ->numeric()
                                        ->prefix('د.أ'),
                                    Forms\Components\TextInput::make('estimated_duration')
                                        ->label('المدة التقديرية')
                                        ->numeric()
                                        ->suffix('شهر'),
                                    Forms\Components\Select::make('currency_id')
                                        ->label('العملة')
                                        ->options(fn () => \App\Models\Currency::where('is_active', true)
                                            ->get()
                                            ->mapWithKeys(fn ($c) => [$c->id => "{$c->code} - {$c->name_ar}"]))
                                        ->default(fn () => \App\Models\Currency::where('is_default', true)->first()?->id ?? 1)
                                        ->searchable()
                                        ->preload(),
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

                            Forms\Components\Section::make('صورة الإعلان')
                                ->collapsible()
                                ->collapsed()
                                ->schema([
                                    Forms\Components\FileUpload::make('announcement_image')
                                        ->label('صورة إعلان العطاء')
                                        ->image()
                                        ->imageEditor()
                                        ->directory('tenders/announcements')
                                        ->maxSize(5120),
                                ]),

                            Forms\Components\Section::make('ملاحظات إضافية')
                                ->collapsible()
                                ->collapsed()
                                ->schema([
                                    Forms\Components\Toggle::make('is_package_tender')
                                        ->label('مناقصة مجزأة (حزم)')
                                        ->live(),
                                    Forms\Components\TextInput::make('package_count')
                                        ->label('عدد الحزم')
                                        ->numeric()
                                        ->visible(fn (Forms\Get $get) => $get('is_package_tender')),
                                    Forms\Components\Textarea::make('additional_notes')
                                        ->label('ملاحظات')
                                        ->rows(4)
                                        ->placeholder('أي ملاحظات إضافية...')
                                        ->columnSpanFull(),
                                ]),
                        ]),
                ])
                ->skippable()
                ->persistStepInQueryString()
                ->columnSpanFull(),
            ]);
    }

    /**
     * البحث عن عطاءات مكررة
     */
    protected function checkForDuplicates(): void
    {
        $data = $this->form->getState();
        
        if (empty($data['reference_number']) && empty($data['name_ar']) && empty($data['customer_id'])) {
            $this->showDuplicateWarning = false;
            return;
        }

        $similar = $this->findSimilarTenders($data);
        
        if (!empty($similar)) {
            $this->similarTenders = $similar;
            $this->showDuplicateWarning = true;
        } else {
            $this->showDuplicateWarning = false;
        }
    }

    /**
     * البحث عن عطاءات مشابهة
     */
    protected function findSimilarTenders(array $data): array
    {
        $similar = [];
        $query = Tender::query();
        $allTenders = $query->get();
        
        $inputRefNumber = $this->normalizeText($data['reference_number'] ?? '');
        $inputCustomerId = $data['customer_id'] ?? null;
        $inputName = $this->normalizeText($data['name_ar'] ?? $data['name_en'] ?? '');
        
        foreach ($allTenders as $tender) {
            $matchReasons = [];
            $matchScore = 0;
            
            // مطابقة رقم المناقصة
            $tenderRefNumber = $this->normalizeText($tender->reference_number);
            if ($inputRefNumber && $tenderRefNumber && $inputRefNumber === $tenderRefNumber) {
                $matchReasons[] = '⛔ رقم المناقصة متطابق تماماً';
                $matchScore += 50;
            }
            
            // مطابقة الجهة المشترية
            if ($inputCustomerId && $tender->customer_id && $inputCustomerId == $tender->customer_id) {
                $matchReasons[] = 'نفس الجهة المشترية';
                $matchScore += 25;
            }
            
            // مطابقة اسم المناقصة
            $tenderName = $this->normalizeText($tender->name_ar ?? $tender->name_en);
            if ($inputName && $tenderName && $inputName === $tenderName) {
                $matchReasons[] = '⛔ اسم المناقصة متطابق تماماً';
                $matchScore += 40;
            }
            
            if ($matchScore >= 40) {
                $similar[] = [
                    'id' => $tender->id,
                    'reference_number' => $tender->reference_number,
                    'name' => $tender->name_ar ?? $tender->name_en,
                    'customer' => $tender->customer?->company_name ?? 'غير محدد',
                    'status' => $tender->status?->getLabel() ?? $tender->status,
                    'match_reasons' => $matchReasons,
                    'match_score' => $matchScore,
                ];
            }
        }
        
        usort($similar, fn($a, $b) => $b['match_score'] <=> $a['match_score']);
        
        return array_slice($similar, 0, 5);
    }

    /**
     * تنظيف النص للمقارنة
     */
    protected function normalizeText(?string $text): string
    {
        if (!$text) return '';
        $text = trim($text);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = preg_replace('/[-_\/\\\\]/', '', $text);
        return mb_strtolower($text);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = TenderStatus::NEW;
        $data['created_by'] = Auth::id();
        $data['tender_scope'] = $data['tender_scope'] ?? 'local';
        
        return $data;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم رصد العطاء بنجاح';
    }

    protected function getRedirectUrl(): string
    {
        return TenderResource::getUrl('view', ['record' => $this->record]);
    }
}
