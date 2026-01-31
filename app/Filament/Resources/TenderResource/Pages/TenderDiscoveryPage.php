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
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * صفحة الرصد والتسجيل - المرحلة الأولى
 * الصلاحية المطلوبة: tenders.discovery.access
 * 
 * تشمل:
 * - البيانات الأساسية
 * - الجهة المشترية
 * - التواريخ مع التحقق
 * - شراء الوثائق
 * - الكفالات
 * - التأهيل والتصنيف
 * - الموقع الجغرافي
 * - صندوق العروض
 * - التمويل
 * - المستندات المرفقة
 * - صورة الإعلان
 * - إجراء الإرسال للدراسة
 */
class TenderDiscoveryPage extends EditRecord
{
    protected static string $resource = TenderResource::class;

    protected static ?string $title = 'الرصد والتسجيل';

    protected static ?string $navigationLabel = 'الرصد والتسجيل';

    /**
     * التحقق من صلاحية الوصول
     */
    public static function canAccess(array $parameters = []): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        
        if ($user->isSuperAdmin()) return true;
        
        return $user->hasAnyPermission([
            'tenders.discovery.access',
            'tenders.tender.update',
        ]);
    }

    /**
     * التحقق من إمكانية التعديل
     */
    public function canEdit(): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        
        if ($user->isSuperAdmin()) return true;
        
        // فقط في مرحلة الرصد (جديد) يمكن التعديل
        if (!in_array($this->record->status, [TenderStatus::NEW])) {
            return $user->hasPermission('tenders.discovery.edit_any_stage');
        }
        
        return $user->hasAnyPermission([
            'tenders.discovery.edit',
            'tenders.tender.update',
        ]);
    }

    public function form(Form $form): Form
    {
        $canEdit = $this->canEdit();
        
        return $form
            ->schema([
                // ===== 1. نوع الفرصة =====
                Forms\Components\Section::make('نوع الفرصة')
                    ->description('تحديد نوع الفرصة: عطاء حكومي أو بيع مباشر')
                    ->icon('heroicon-o-tag')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Toggle::make('is_direct_sale')
                            ->label('فرصة بيع مباشر')
                            ->helperText('عند التفعيل: يتم إلغاء شراء الوثائق والكفالات')
                            ->live()
                            ->disabled(!$canEdit)
                            ->default(false),
                        Forms\Components\Toggle::make('is_english_tender')
                            ->label('باللغة الإنجليزية')
                            ->live()
                            ->disabled(!$canEdit)
                            ->default(false),
                        Forms\Components\Select::make('customer_id')
                            ->label('العميل')
                            ->relationship('customer', 'company_name')
                            ->searchable()
                            ->preload()
                            ->disabled(!$canEdit)
                            ->visible(fn (Forms\Get $get) => $get('is_direct_sale')),
                    ]),

                // ===== 2. البيانات الأساسية =====
                Forms\Components\Section::make('معلومات العطاء الأساسية')
                    ->description('البيانات التعريفية للعطاء')
                    ->icon('heroicon-o-document-text')
                    ->columns(4)
                    ->schema([
                        Forms\Components\TextInput::make('tender_number')
                            ->label('رقم العطاء في النظام')
                            ->disabled()
                            ->placeholder('تلقائي'),
                        Forms\Components\TextInput::make('reference_number')
                            ->label('رقم المناقصة من المالك')
                            ->required()
                            ->maxLength(50)
                            ->disabled(!$canEdit),
                        Forms\Components\Select::make('tender_scope')
                            ->label('نطاق العطاء')
                            ->options([
                                'local' => 'محلي',
                                'international' => 'دولي',
                            ])
                            ->default('local')
                            ->disabled(!$canEdit)
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options(TenderStatus::class)
                            ->disabled(),
                        Forms\Components\TextInput::make('name_ar')
                            ->label('اسم العطاء (عربي)')
                            ->required(fn (Forms\Get $get) => !$get('is_english_tender'))
                            ->hidden(fn (Forms\Get $get) => $get('is_english_tender'))
                            ->maxLength(255)
                            ->disabled(!$canEdit)
                            ->columnSpan(2),
                        Forms\Components\TextInput::make('name_en')
                            ->label('اسم العطاء (إنجليزي)')
                            ->required(fn (Forms\Get $get) => $get('is_english_tender'))
                            ->visible(fn (Forms\Get $get) => $get('is_english_tender'))
                            ->maxLength(255)
                            ->disabled(!$canEdit)
                            ->columnSpan(2),
                        Forms\Components\Textarea::make('description')
                            ->label('وصف الأشغال المطلوبة')
                            ->rows(3)
                            ->disabled(!$canEdit)
                            ->columnSpanFull(),
                    ]),

                // ===== 3. تصنيف العطاء =====
                Forms\Components\Section::make('تصنيف العطاء')
                    ->icon('heroicon-o-squares-2x2')
                    ->columns(4)
                    ->schema([
                        Forms\Components\Select::make('tender_type')
                            ->label('نوع العطاء')
                            ->options(TenderType::class)
                            ->disabled(!$canEdit)
                            ->required(),
                        Forms\Components\Select::make('tender_method')
                            ->label('أسلوب الطرح')
                            ->options(TenderMethod::class)
                            ->disabled(!$canEdit)
                            ->required(),
                        Forms\Components\Select::make('project_type_id')
                            ->label('نوع المشروع')
                            ->relationship('projectType', 'name_ar')
                            ->searchable()
                            ->preload()
                            ->disabled(!$canEdit),
                        Forms\Components\Select::make('priority')
                            ->label('الأولوية')
                            ->options([
                                'high' => 'عالية',
                                'medium' => 'متوسطة',
                                'low' => 'منخفضة',
                            ])
                            ->disabled(!$canEdit),
                    ]),

                // ===== 4. الجهة المالكة =====
                Forms\Components\Section::make('الجهة المالكة / المشترية')
                    ->icon('heroicon-o-building-library')
                    ->columns(4)
                    ->schema([
                        Forms\Components\Select::make('owner_type')
                            ->label('نوع الجهة')
                            ->options(OwnerType::class)
                            ->disabled(!$canEdit)
                            ->required(),
                        Forms\Components\Select::make('customer_id')
                            ->label('الجهة المالكة')
                            ->relationship('customer', 'company_name')
                            ->searchable()
                            ->preload()
                            ->disabled(!$canEdit)
                            ->createOptionForm([
                                Forms\Components\TextInput::make('company_name')
                                    ->label('اسم الجهة')
                                    ->required(),
                                Forms\Components\TextInput::make('phone')
                                    ->label('الهاتف')
                                    ->tel(),
                                Forms\Components\TextInput::make('email')
                                    ->label('البريد')
                                    ->email(),
                            ])
                            ->columnSpan(2),
                        Forms\Components\TextInput::make('owner_contact_person')
                            ->label('جهة الاتصال')
                            ->disabled(!$canEdit)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('owner_phone')
                            ->label('الهاتف')
                            ->tel()
                            ->disabled(!$canEdit),
                        Forms\Components\TextInput::make('owner_email')
                            ->label('البريد الإلكتروني')
                            ->email()
                            ->disabled(!$canEdit),
                        Forms\Components\TextInput::make('owner_website')
                            ->label('الموقع الإلكتروني')
                            ->url()
                            ->disabled(!$canEdit),
                    ]),

                // ===== 5. التواريخ مع التحقق =====
                Forms\Components\Section::make('التواريخ الأساسية')
                    ->description('⚠️ يتم التحقق من تسلسل التواريخ تلقائياً')
                    ->icon('heroicon-o-calendar')
                    ->columns(4)
                    ->schema([
                        Forms\Components\DatePicker::make('publication_date')
                            ->label('تاريخ الإعلان')
                            ->required()
                            ->default(now())
                            ->live()
                            ->disabled(!$canEdit),
                        Forms\Components\DatePicker::make('documents_sale_start')
                            ->label('بداية بيع الوثائق')
                            ->hidden(fn (Forms\Get $get) => $get('is_direct_sale'))
                            ->minDate(fn (Forms\Get $get) => $get('publication_date') ?: now())
                            ->disabled(!$canEdit),
                        Forms\Components\DateTimePicker::make('documents_sale_end')
                            ->label('نهاية بيع الوثائق')
                            ->hidden(fn (Forms\Get $get) => $get('is_direct_sale'))
                            ->minDate(fn (Forms\Get $get) => $get('documents_sale_start') ?: now())
                            ->disabled(!$canEdit),
                        Forms\Components\DateTimePicker::make('questions_deadline')
                            ->label('آخر موعد للاستفسارات')
                            ->minDate(fn (Forms\Get $get) => $get('publication_date') ?: now())
                            ->disabled(!$canEdit),
                        Forms\Components\DateTimePicker::make('site_visit_date')
                            ->label('موعد زيارة الموقع')
                            ->minDate(fn (Forms\Get $get) => $get('publication_date') ?: now())
                            ->disabled(!$canEdit),
                        Forms\Components\DateTimePicker::make('pre_bid_meeting_date')
                            ->label('اجتماع ما قبل المناقصة')
                            ->disabled(!$canEdit),
                        Forms\Components\DateTimePicker::make('submission_deadline')
                            ->label('⚠️ آخر موعد للتقديم')
                            ->required()
                            ->live()
                            ->minDate(fn (Forms\Get $get) => $get('publication_date') ? \Carbon\Carbon::parse($get('publication_date'))->addDay() : now()->addDay())
                            ->disabled(!$canEdit)
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
                            ->label('تاريخ الفتح')
                            ->hidden(fn (Forms\Get $get) => $get('is_direct_sale'))
                            ->minDate(fn (Forms\Get $get) => $get('submission_deadline') ?: now())
                            ->disabled(!$canEdit),
                        Forms\Components\TextInput::make('validity_period')
                            ->label('فترة صلاحية العرض')
                            ->numeric()
                            ->suffix('يوم')
                            ->default(90)
                            ->disabled(!$canEdit),
                    ]),

                // ===== 6. شراء الوثائق =====
                Forms\Components\Section::make('شراء وثائق المناقصة')
                    ->icon('heroicon-o-document-text')
                    ->hidden(fn (Forms\Get $get) => $get('is_direct_sale'))
                    ->collapsible()
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('documents_price')
                            ->label('ثمن الوثائق (غير مستردة)')
                            ->numeric()
                            ->suffix('د.أ')
                            ->disabled(!$canEdit),
                        Forms\Components\Select::make('electronic_submission')
                            ->label('التقديم الإلكتروني')
                            ->options([
                                'accepted' => 'مقبول',
                                'not_accepted' => 'غير مقبول',
                            ])
                            ->default('not_accepted')
                            ->disabled(!$canEdit),
                        Forms\Components\TextInput::make('clarification_address')
                            ->label('عنوان الاستيضاحات')
                            ->disabled(!$canEdit),
                        Forms\Components\Repeater::make('required_documents')
                            ->label('الأوراق المطلوبة لشراء الوثائق')
                            ->columnSpanFull()
                            ->collapsible()
                            ->disabled(!$canEdit)
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

                // ===== 7. الكفالة الابتدائية =====
                Forms\Components\Section::make('تأمين دخول العطاء (الكفالة)')
                    ->icon('heroicon-o-banknotes')
                    ->hidden(fn (Forms\Get $get) => $get('is_direct_sale'))
                    ->collapsible()
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
                            ->default('guarantee_or_certified')
                            ->disabled(!$canEdit),
                        Forms\Components\Select::make('bid_bond_calculation')
                            ->label('طريقة الحساب')
                            ->options([
                                'fixed' => 'مبلغ ثابت',
                                'percentage' => 'نسبة مئوية',
                            ])
                            ->live()
                            ->default('fixed')
                            ->disabled(!$canEdit),
                        Forms\Components\TextInput::make('bid_bond_amount')
                            ->label('قيمة التأمين')
                            ->numeric()
                            ->suffix('د.أ')
                            ->visible(fn (Forms\Get $get) => $get('bid_bond_calculation') === 'fixed')
                            ->disabled(!$canEdit),
                        Forms\Components\TextInput::make('bid_bond_percentage')
                            ->label('نسبة التأمين')
                            ->numeric()
                            ->suffix('%')
                            ->visible(fn (Forms\Get $get) => $get('bid_bond_calculation') === 'percentage')
                            ->disabled(!$canEdit),
                        Forms\Components\TextInput::make('bid_bond_validity_days')
                            ->label('مدة سريان التأمين')
                            ->numeric()
                            ->suffix('يوم')
                            ->disabled(!$canEdit),
                    ]),

                // ===== 8. متطلبات التأهيل =====
                Forms\Components\Section::make('متطلبات التأهيل والتصنيف')
                    ->icon('heroicon-o-academic-cap')
                    ->collapsible()
                    ->columns(4)
                    ->schema([
                        Forms\Components\Select::make('classification_field_id')
                            ->label('المجال')
                            ->options(\App\Models\ClassificationField::pluck('name_ar', 'id'))
                            ->searchable()
                            ->disabled(!$canEdit),
                        Forms\Components\Select::make('classification_specialty_id')
                            ->label('الاختصاص')
                            ->options(\App\Models\ClassificationSpecialty::pluck('name_ar', 'id'))
                            ->searchable()
                            ->disabled(!$canEdit),
                        Forms\Components\Select::make('classification_category_id')
                            ->label('الفئة')
                            ->options(\App\Models\ClassificationCategory::pluck('name_ar', 'id'))
                            ->searchable()
                            ->disabled(!$canEdit),
                        Forms\Components\TextInput::make('classification_scope')
                            ->label('النطاق المالي')
                            ->disabled(!$canEdit),
                        Forms\Components\TextInput::make('minimum_experience_years')
                            ->label('الخبرة المطلوبة')
                            ->numeric()
                            ->suffix('سنة')
                            ->disabled(!$canEdit),
                        Forms\Components\TextInput::make('minimum_similar_projects')
                            ->label('مشاريع مماثلة')
                            ->numeric()
                            ->disabled(!$canEdit),
                        Forms\Components\TextInput::make('minimum_project_value')
                            ->label('الحد الأدنى للقيمة')
                            ->numeric()
                            ->prefix('د.أ')
                            ->disabled(!$canEdit),
                    ]),

                // ===== 9. الموقع الجغرافي =====
                Forms\Components\Section::make('موقع المشروع')
                    ->icon('heroicon-o-map-pin')
                    ->collapsible()
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
                            ->live()
                            ->disabled(!$canEdit),
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
                            ->searchable()
                            ->disabled(!$canEdit),
                        Forms\Components\TextInput::make('project_district')
                            ->label('المنطقة/الحي')
                            ->disabled(!$canEdit),
                        Forms\Components\TextInput::make('google_maps_link')
                            ->label('رابط جوجل ماب')
                            ->url()
                            ->disabled(!$canEdit),
                        Forms\Components\Textarea::make('site_address')
                            ->label('العنوان التفصيلي')
                            ->rows(2)
                            ->columnSpanFull()
                            ->disabled(!$canEdit),
                    ]),

                // ===== 10. عنوان التقديم =====
                Forms\Components\Section::make('عنوان صندوق العروض')
                    ->icon('heroicon-o-inbox-arrow-down')
                    ->collapsible()
                    ->columns(4)
                    ->schema([
                        Forms\Components\TextInput::make('submission_city')
                            ->label('المدينة')
                            ->disabled(!$canEdit),
                        Forms\Components\TextInput::make('submission_district')
                            ->label('المنطقة')
                            ->disabled(!$canEdit),
                        Forms\Components\TextInput::make('submission_street')
                            ->label('الشارع')
                            ->disabled(!$canEdit),
                        Forms\Components\TextInput::make('submission_building')
                            ->label('المبنى/الطابق')
                            ->disabled(!$canEdit),
                        Forms\Components\TextInput::make('submission_box_number')
                            ->label('رقم الصندوق')
                            ->disabled(!$canEdit),
                        Forms\Components\Textarea::make('submission_notes')
                            ->label('ملاحظات')
                            ->rows(2)
                            ->columnSpan(3)
                            ->disabled(!$canEdit),
                    ]),

                // ===== 11. القيم المالية =====
                Forms\Components\Section::make('القيم المالية والتمويل')
                    ->icon('heroicon-o-currency-dollar')
                    ->collapsible()
                    ->columns(4)
                    ->schema([
                        Forms\Components\TextInput::make('estimated_value')
                            ->label('القيمة التقديرية')
                            ->numeric()
                            ->prefix('د.أ')
                            ->disabled(!$canEdit),
                        Forms\Components\TextInput::make('estimated_duration')
                            ->label('المدة التقديرية')
                            ->numeric()
                            ->suffix('شهر')
                            ->disabled(!$canEdit),
                        Forms\Components\Select::make('currency_id')
                            ->label('العملة')
                            ->options(fn () => \App\Models\Currency::where('is_active', true)
                                ->get()
                                ->mapWithKeys(fn ($c) => [$c->id => "{$c->code} - {$c->name_ar}"]))
                            ->default(fn () => \App\Models\Currency::where('is_default', true)->first()?->id ?? 1)
                            ->searchable()
                            ->disabled(!$canEdit),
                        Forms\Components\Select::make('funding_source')
                            ->label('مصدر التمويل')
                            ->options([
                                'government_budget' => 'الموازنة العامة',
                                'funded_project' => 'مشروع ممول',
                                'loan' => 'قرض',
                                'grant' => 'منحة',
                            ])
                            ->default('government_budget')
                            ->live()
                            ->disabled(!$canEdit),
                        Forms\Components\TextInput::make('funder_name')
                            ->label('اسم الممول')
                            ->visible(fn (Forms\Get $get) => $get('funding_source') !== 'government_budget')
                            ->columnSpan(2)
                            ->disabled(!$canEdit),
                    ]),

                // ===== 12. صورة الإعلان =====
                Forms\Components\Section::make('صورة الإعلان')
                    ->icon('heroicon-o-photo')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\FileUpload::make('announcement_image')
                            ->label('صورة إعلان العطاء')
                            ->image()
                            ->imageEditor()
                            ->directory('tenders/announcements')
                            ->maxSize(5120) // 5MB
                            ->disabled(!$canEdit),
                    ]),

                // ===== 13. المرفقات =====
                Forms\Components\Section::make('المستندات المرفقة')
                    ->icon('heroicon-o-paper-clip')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\Repeater::make('tender_attachments')
                            ->label('')
                            ->relationship('attachments')
                            ->disabled(!$canEdit)
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->label('عنوان المرفق')
                                    ->required(),
                                Forms\Components\FileUpload::make('file_path')
                                    ->label('الملف')
                                    ->directory('tenders/attachments')
                                    ->required(),
                                Forms\Components\Select::make('type')
                                    ->label('النوع')
                                    ->options([
                                        'tender_documents' => 'وثائق المناقصة',
                                        'addendum' => 'ملحق',
                                        'clarification' => 'استيضاح',
                                        'other' => 'أخرى',
                                    ])
                                    ->default('tender_documents'),
                            ])
                            ->columns(3)
                            ->addActionLabel('+ إضافة مرفق')
                            ->defaultItems(0),
                    ]),

                // ===== 14. ملاحظات =====
                Forms\Components\Section::make('ملاحظات إضافية')
                    ->icon('heroicon-o-pencil-square')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\Textarea::make('additional_notes')
                            ->label('')
                            ->rows(4)
                            ->placeholder('أي ملاحظات إضافية...')
                            ->disabled(!$canEdit),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back_to_view')
                ->label('العودة للعرض')
                ->icon('heroicon-o-arrow-right')
                ->color('gray')
                ->url(fn () => TenderResource::getUrl('view', ['record' => $this->record])),

            // ===== زر فحص اكتمال البيانات =====
            Actions\Action::make('check_validation')
                ->label('فحص الوثائق')
                ->icon('heroicon-o-clipboard-document-check')
                ->color('info')
                ->modalHeading('تدقيق وثائق العطاء')
                ->modalDescription('نتائج فحص اكتمال بيانات ووثائق العطاء')
                ->modalIcon('heroicon-o-clipboard-document-check')
                ->modalWidth('2xl')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('إغلاق')
                ->action(fn () => null)
                ->modalContent(function (): \Illuminate\Contracts\View\View {
                    $summary = $this->getValidationSummary();
                    $totalErrors = count($summary['overall']);
                    $hasWarnings = collect($summary['overall'])->filter(fn ($e) => str_contains($e, 'تحذير'))->count();
                    $hasErrors = $totalErrors - $hasWarnings;
                    
                    return view('filament.modals.validation-summary', [
                        'summary' => $summary,
                        'totalErrors' => $hasErrors,
                        'totalWarnings' => $hasWarnings,
                        'isReady' => $hasErrors === 0,
                    ]);
                }),

            Actions\Action::make('send_to_study')
                ->label('إرسال للدراسة')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->visible(fn () => $this->record->status === TenderStatus::NEW && $this->canEdit())
                ->requiresConfirmation()
                ->modalHeading('إرسال العطاء للدراسة')
                ->modalDescription('سيتم نقل العطاء لمرحلة الدراسة والتقييم. هل أنت متأكد؟')
                ->modalIcon('heroicon-o-paper-airplane')
                ->action(function () {
                    // التحقق من اكتمال البيانات الأساسية
                    $errors = $this->validateForStudy();
                    
                    // فصل الأخطاء عن التحذيرات
                    $criticalErrors = collect($errors)->filter(fn ($e) => !str_contains($e, 'تحذير'))->values()->all();
                    $warnings = collect($errors)->filter(fn ($e) => str_contains($e, 'تحذير'))->values()->all();
                    
                    if (!empty($criticalErrors)) {
                        Notification::make()
                            ->title('لا يمكن الإرسال للدراسة')
                            ->body(implode("\n", $criticalErrors))
                            ->danger()
                            ->persistent()
                            ->send();
                        return;
                    }
                    
                    // عرض التحذيرات إن وجدت
                    if (!empty($warnings)) {
                        Notification::make()
                            ->title('تحذيرات')
                            ->body(implode("\n", $warnings))
                            ->warning()
                            ->send();
                    }

                    DB::transaction(function () {
                        $this->record->update([
                            'status' => TenderStatus::STUDYING,
                            'sent_to_study_at' => now(),
                            'sent_to_study_by' => Auth::id(),
                        ]);

                        // إنشاء سجل في تاريخ المراحل
                        $this->createStageLog('studying', 'تم إرسال العطاء للدراسة');

                        // إرسال إشعار للمسؤولين عن الدراسة
                        $this->notifyStudyTeam();
                    });
                    
                    Notification::make()
                        ->title('تم إرسال العطاء للدراسة')
                        ->success()
                        ->send();
                    
                    $this->redirect(TenderResource::getUrl('view', ['record' => $this->record]));
                }),
        ];
    }

    /**
     * التحقق من اكتمال البيانات قبل الإرسال للدراسة
     */
    protected function validateForStudy(): array
    {
        $errors = [];

        // ===== 1. البيانات الأساسية =====
        if (empty($this->record->name_ar) && empty($this->record->name_en)) {
            $errors[] = '• اسم العطاء مطلوب (عربي أو إنجليزي)';
        }

        if (empty($this->record->reference_number)) {
            $errors[] = '• رقم المناقصة مطلوب';
        }

        if (empty($this->record->tender_type)) {
            $errors[] = '• نوع العطاء مطلوب';
        }

        if (empty($this->record->tender_method)) {
            $errors[] = '• طريقة الطرح مطلوبة';
        }

        // ===== 2. الجهة المشترية =====
        if (empty($this->record->customer_id) && empty($this->record->owner_type)) {
            $errors[] = '• الجهة المالكة مطلوبة (العميل أو نوع المالك)';
        }

        // ===== 3. التواريخ الأساسية =====
        $dateErrors = $this->validateDates();
        $errors = array_merge($errors, $dateErrors);

        // ===== 4. وثائق المناقصة (إذا لم يكن بيع مباشر) =====
        if (!$this->record->is_direct_sale) {
            $documentErrors = $this->validateDocuments();
            $errors = array_merge($errors, $documentErrors);
        }

        // ===== 5. الكفالات (إذا لم يكن بيع مباشر) =====
        if (!$this->record->is_direct_sale) {
            $bondErrors = $this->validateBonds();
            $errors = array_merge($errors, $bondErrors);
        }

        // ===== 6. البيانات الجغرافية =====
        if (empty($this->record->country_id) && empty($this->record->city_id)) {
            $errors[] = '• الموقع الجغرافي مطلوب (الدولة أو المدينة)';
        }

        return $errors;
    }

    /**
     * شروط تدقيق التواريخ
     * Date validation conditions
     */
    protected function validateDates(): array
    {
        $errors = [];

        // تاريخ النشر
        if (empty($this->record->publication_date)) {
            $errors[] = '• تاريخ النشر/الإعلان مطلوب';
        }

        // موعد التقديم
        if (empty($this->record->submission_deadline)) {
            $errors[] = '• موعد التقديم مطلوب';
        }

        // التحقق من تسلسل التواريخ
        if ($this->record->publication_date && $this->record->submission_deadline) {
            $pubDate = \Carbon\Carbon::parse($this->record->publication_date);
            $subDate = \Carbon\Carbon::parse($this->record->submission_deadline);
            
            // موعد التقديم يجب أن يكون بعد تاريخ النشر
            if ($subDate->lte($pubDate)) {
                $errors[] = '• موعد التقديم يجب أن يكون بعد تاريخ النشر';
            }
            
            // موعد التقديم يجب أن يكون بعد اليوم الحالي
            if ($subDate->lt(now())) {
                $errors[] = '• ⚠️ تحذير: موعد التقديم قد انتهى';
            }
            
            // الحد الأدنى للمدة بين النشر والتقديم (7 أيام على الأقل)
            $daysDiff = $pubDate->diffInDays($subDate);
            if ($daysDiff < 7) {
                $errors[] = "• تحذير: الفترة بين النشر والتقديم ({$daysDiff} أيام) قصيرة جداً (الحد الأدنى المقترح: 7 أيام)";
            }
        }

        // التحقق من تواريخ بيع الوثائق
        if (!$this->record->is_direct_sale) {
            if ($this->record->documents_sale_start && $this->record->publication_date) {
                $pubDate = \Carbon\Carbon::parse($this->record->publication_date);
                $saleStart = \Carbon\Carbon::parse($this->record->documents_sale_start);
                if ($saleStart->lt($pubDate)) {
                    $errors[] = '• تاريخ بدء بيع الوثائق يجب أن يكون بعد أو يساوي تاريخ النشر';
                }
            }
            
            if ($this->record->documents_sale_start && $this->record->documents_sale_end) {
                $saleStart = \Carbon\Carbon::parse($this->record->documents_sale_start);
                $saleEnd = \Carbon\Carbon::parse($this->record->documents_sale_end);
                if ($saleEnd->lt($saleStart)) {
                    $errors[] = '• تاريخ انتهاء بيع الوثائق يجب أن يكون بعد تاريخ البدء';
                }
            }
            
            // تاريخ انتهاء بيع الوثائق يجب أن يكون قبل موعد التقديم
            if ($this->record->documents_sale_end && $this->record->submission_deadline) {
                $saleEnd = \Carbon\Carbon::parse($this->record->documents_sale_end);
                $subDate = \Carbon\Carbon::parse($this->record->submission_deadline);
                if ($saleEnd->gt($subDate)) {
                    $errors[] = '• تاريخ انتهاء بيع الوثائق يجب أن يكون قبل موعد التقديم';
                }
            }
        }

        // التحقق من موعد الاستيضاحات
        if ($this->record->questions_deadline && $this->record->submission_deadline) {
            $qDeadline = \Carbon\Carbon::parse($this->record->questions_deadline);
            $subDate = \Carbon\Carbon::parse($this->record->submission_deadline);
            
            // موعد الاستيضاحات يجب أن يكون قبل موعد التقديم بـ 3 أيام على الأقل
            if ($qDeadline->gte($subDate)) {
                $errors[] = '• موعد انتهاء الاستيضاحات يجب أن يكون قبل موعد التقديم';
            } elseif ($qDeadline->diffInDays($subDate) < 3) {
                $errors[] = '• تحذير: يجب أن يكون هناك 3 أيام على الأقل بين موعد الاستيضاحات وموعد التقديم';
            }
        }

        // التحقق من تاريخ زيارة الموقع
        if ($this->record->site_visit_date && $this->record->submission_deadline) {
            $visitDate = \Carbon\Carbon::parse($this->record->site_visit_date);
            $subDate = \Carbon\Carbon::parse($this->record->submission_deadline);
            
            if ($visitDate->gte($subDate)) {
                $errors[] = '• تاريخ زيارة الموقع يجب أن يكون قبل موعد التقديم';
            }
        }

        // التحقق من تاريخ الفتح
        if ($this->record->opening_date && $this->record->submission_deadline) {
            $openDate = \Carbon\Carbon::parse($this->record->opening_date);
            $subDate = \Carbon\Carbon::parse($this->record->submission_deadline);
            
            if ($openDate->lt($subDate)) {
                $errors[] = '• تاريخ الفتح يجب أن يكون بعد أو يساوي موعد التقديم';
            }
        }

        return $errors;
    }

    /**
     * شروط تدقيق الوثائق المطلوبة
     * Document validation conditions
     */
    protected function validateDocuments(): array
    {
        $errors = [];

        // التحقق من وجود ثمن الوثائق (إذا كان هناك وثائق للشراء)
        if ($this->record->documents_sale_start && empty($this->record->documents_price)) {
            $errors[] = '• ثمن الوثائق مطلوب إذا تم تحديد تاريخ بيع الوثائق';
        }

        // التحقق من وجود المستندات المرفقة الأساسية
        $attachments = $this->record->attachments ?? collect();
        $hasAdvertisement = false;
        $hasTenderDocuments = false;
        
        foreach ($attachments as $attachment) {
            $type = $attachment->type ?? $attachment['type'] ?? null;
            if ($type === 'advertisement') {
                $hasAdvertisement = true;
            }
            if ($type === 'tender_documents') {
                $hasTenderDocuments = true;
            }
        }

        // التحقق من صورة الإعلان (اختياري لكن موصى به)
        if (!$hasAdvertisement && empty($this->record->announcement_image)) {
            // تحذير وليس خطأ
            $errors[] = '• تحذير: يُنصح بإرفاق صورة الإعلان أو نص الإعلان';
        }

        // التحقق من الوثائق المطلوبة لشراء الوثائق
        $requiredDocs = $this->record->required_documents ?? [];
        if (!empty($requiredDocs)) {
            foreach ($requiredDocs as $index => $doc) {
                if (empty($doc['document_name'] ?? null)) {
                    $errors[] = "• الوثيقة المطلوبة #{$index}: اسم الوثيقة فارغ";
                }
            }
        }

        return $errors;
    }

    /**
     * شروط تدقيق الكفالات
     * Bond validation conditions
     */
    protected function validateBonds(): array
    {
        $errors = [];

        // التحقق من كفالة الدخول (إذا كانت مطلوبة)
        if ($this->record->bid_bond_required) {
            if (empty($this->record->bid_bond_amount) && empty($this->record->bid_bond_percentage)) {
                $errors[] = '• كفالة الدخول مطلوبة: يجب تحديد المبلغ أو النسبة';
            }
        }

        // التحقق من صلاحية الكفالة
        if ($this->record->bid_bond_validity_days) {
            // يجب أن تكون صلاحية الكفالة أكبر من فترة صلاحية العرض
            $offerValidity = $this->record->validity_period ?? 90;
            if ($this->record->bid_bond_validity_days < $offerValidity) {
                $errors[] = "• صلاحية كفالة الدخول ({$this->record->bid_bond_validity_days} يوم) يجب أن تكون أكبر من أو تساوي فترة صلاحية العرض ({$offerValidity} يوم)";
            }
        }

        return $errors;
    }

    /**
     * التحقق الكامل من جاهزية العطاء للمرحلة التالية
     * يمكن استخدامه من أي مكان في النظام
     */
    public function getValidationSummary(): array
    {
        return [
            'basic' => $this->validateBasicInfo(),
            'dates' => $this->validateDates(),
            'documents' => $this->validateDocuments(),
            'bonds' => $this->validateBonds(),
            'overall' => $this->validateForStudy(),
        ];
    }

    /**
     * التحقق من البيانات الأساسية فقط
     */
    protected function validateBasicInfo(): array
    {
        $errors = [];

        if (empty($this->record->name_ar) && empty($this->record->name_en)) {
            $errors[] = '• اسم العطاء مطلوب';
        }

        if (empty($this->record->reference_number)) {
            $errors[] = '• رقم المناقصة مطلوب';
        }

        if (empty($this->record->tender_type)) {
            $errors[] = '• نوع العطاء مطلوب';
        }

        if (empty($this->record->tender_method)) {
            $errors[] = '• طريقة الطرح مطلوبة';
        }

        if (empty($this->record->customer_id) && empty($this->record->owner_type)) {
            $errors[] = '• الجهة المالكة مطلوبة';
        }

        return $errors;
    }

    /**
     * إنشاء سجل في تاريخ المراحل
     */
    protected function createStageLog(string $stage, string $notes): void
    {
        // إذا كان هناك موديل TenderStageLog
        if (class_exists(\App\Models\TenderStageLog::class)) {
            \App\Models\TenderStageLog::create([
                'tender_id' => $this->record->id,
                'stage' => $stage,
                'notes' => $notes,
                'user_id' => Auth::id(),
            ]);
        }
    }

    /**
     * إرسال إشعار لفريق الدراسة
     */
    protected function notifyStudyTeam(): void
    {
        // يمكن إضافة منطق الإشعارات هنا
        // مثلاً إرسال إشعار لجميع المستخدمين الذين لديهم صلاحية tenders.study.access
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم حفظ بيانات الرصد';
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // التأكد من القيم الافتراضية
        $data['tender_scope'] = $data['tender_scope'] ?? 'local';
        $data['updated_by'] = Auth::id();
        
        return $data;
    }
}
