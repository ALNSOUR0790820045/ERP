<?php

namespace App\Filament\Resources\TenderResource\Pages;

use App\Enums\TenderStatus;
use App\Filament\Resources\TenderResource;
use App\Filament\Resources\TenderResource\Widgets\TenderStatsWidget;
use App\Filament\Resources\TenderResource\Widgets\TenderTimelineWidget;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Components\Tabs;
use Filament\Notifications\Notification;

class ViewTender extends ViewRecord
{
    protected static string $resource = TenderResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            TenderStatsWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            TenderTimelineWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('edit_tender')
                ->label('تعديل')
                ->icon('heroicon-o-pencil')
                ->url(fn () => \App\Filament\Pages\TenderWorkflow::getUrl() . '?record=' . $this->record->id),
            
            // ===== المرحلة 1: الرصد =====
            Actions\Action::make('start_study')
                ->label('بدء الدراسة')
                ->icon('heroicon-o-clipboard-document-list')
                ->color('info')
                ->visible(fn () => $this->record->status === TenderStatus::NEW)
                ->requiresConfirmation()
                ->modalHeading('بدء دراسة العطاء')
                ->modalDescription('سيتم نقل العطاء لمرحلة الدراسة والتقييم')
                ->action(function () {
                    $this->record->update(['status' => TenderStatus::STUDYING]);
                    $this->createStageLog('studying', 'بدء دراسة العطاء');
                    Notification::make()->title('تم بدء الدراسة')->success()->send();
                }),

            // ===== المرحلة 2: الدراسة والقرار =====
            Actions\Action::make('go_no_go')
                ->label('قرار Go/No-Go')
                ->icon('heroicon-o-scale')
                ->color('warning')
                ->visible(fn () => $this->record->status === TenderStatus::STUDYING)
                ->form([
                    \Filament\Forms\Components\Section::make('تقييم العطاء')
                        ->schema([
                            \Filament\Forms\Components\Radio::make('decision')
                                ->label('القرار')
                                ->options([
                                    'go' => '✅ Go - المشاركة في العطاء',
                                    'no_go' => '❌ No-Go - عدم المشاركة',
                                ])
                                ->required()
                                ->inline(),
                            \Filament\Forms\Components\Textarea::make('reason')
                                ->label('مبررات القرار')
                                ->rows(3)
                                ->required(),
                            \Filament\Forms\Components\Select::make('priority')
                                ->label('الأولوية')
                                ->options([
                                    'high' => 'عالية',
                                    'medium' => 'متوسطة', 
                                    'low' => 'منخفضة',
                                ])
                                ->visible(fn ($get) => $get('decision') === 'go'),
                        ]),
                ])
                ->action(function (array $data) {
                    $newStatus = $data['decision'] === 'go' ? TenderStatus::GO : TenderStatus::NO_GO;
                    $this->record->update([
                        'decision' => $data['decision'],
                        'decision_notes' => $data['reason'],
                        'decision_date' => now(),
                        'decision_by' => auth()->id(),
                        'status' => $newStatus,
                        'priority' => $data['priority'] ?? null,
                    ]);
                    $this->createStageLog($newStatus->value, $data['reason']);
                    
                    $msg = $data['decision'] === 'go' ? 'تم اعتماد المشاركة' : 'تم رفض المشاركة';
                    Notification::make()->title($msg)->success()->send();
                }),

            // ===== المرحلة 3: إعداد العرض =====
            Actions\Action::make('start_pricing')
                ->label('بدء التسعير')
                ->icon('heroicon-o-calculator')
                ->color('primary')
                ->visible(fn () => $this->record->status === TenderStatus::GO)
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['status' => TenderStatus::PRICING]);
                    $this->createStageLog('pricing', 'بدء إعداد التسعير');
                    Notification::make()->title('تم بدء التسعير')->success()->send();
                }),

            Actions\Action::make('mark_ready')
                ->label('جاهز للتقديم')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn () => $this->record->status === TenderStatus::PRICING)
                ->form([
                    \Filament\Forms\Components\Section::make('قائمة التحقق')
                        ->schema([
                            \Filament\Forms\Components\CheckboxList::make('checklist')
                                ->label('')
                                ->options([
                                    'boq_complete' => 'جدول الكميات مكتمل',
                                    'technical_complete' => 'العرض الفني جاهز',
                                    'financial_complete' => 'العرض المالي جاهز',
                                    'bond_ready' => 'الكفالة الابتدائية جاهزة',
                                    'documents_ready' => 'الوثائق المطلوبة مكتملة',
                                    'signatures_done' => 'التوقيعات تمت',
                                ])
                                ->required()
                                ->columns(2),
                        ]),
                ])
                ->action(function (array $data) {
                    if (count($data['checklist']) < 6) {
                        Notification::make()
                            ->title('تحذير')
                            ->body('يجب إكمال جميع بنود قائمة التحقق')
                            ->warning()
                            ->send();
                        return;
                    }
                    $this->record->update(['status' => TenderStatus::READY]);
                    $this->createStageLog('ready', 'العرض جاهز للتقديم');
                    Notification::make()->title('العرض جاهز للتقديم')->success()->send();
                }),

            // ===== المرحلة 4: التقديم =====
            Actions\Action::make('submit_tender')
                ->label('تقديم العطاء')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->visible(fn () => $this->record->status === TenderStatus::READY)
                ->form([
                    \Filament\Forms\Components\Section::make('بيانات التقديم')
                        ->schema([
                            \Filament\Forms\Components\DateTimePicker::make('submission_date')
                                ->label('تاريخ ووقت التقديم')
                                ->default(now())
                                ->required(),
                            \Filament\Forms\Components\Select::make('submission_method')
                                ->label('طريقة التقديم')
                                ->options([
                                    'hand' => 'تسليم يدوي',
                                    'mail' => 'بريد مسجل',
                                    'electronic' => 'إلكتروني',
                                    'courier' => 'شركة شحن',
                                ])
                                ->required(),
                            \Filament\Forms\Components\TextInput::make('receipt_number')
                                ->label('رقم الإيصال/المرجع'),
                            \Filament\Forms\Components\TextInput::make('submitted_price')
                                ->label('السعر المقدم')
                                ->numeric()
                                ->prefix('JOD'),
                        ])
                        ->columns(2),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'status' => TenderStatus::SUBMITTED,
                        'submission_date' => $data['submission_date'],
                        'submission_method' => $data['submission_method'],
                        'receipt_number' => $data['receipt_number'],
                        'submitted_price' => $data['submitted_price'],
                        'submitted_by' => auth()->id(),
                    ]);
                    $this->createStageLog('submitted', 'تم تقديم العطاء');
                    Notification::make()->title('تم تقديم العطاء بنجاح')->success()->send();
                }),

            // ===== المرحلة 5: الفتح والنتائج =====
            Actions\Action::make('record_opening')
                ->label('تسجيل نتائج الفتح')
                ->icon('heroicon-o-envelope-open')
                ->color('info')
                ->visible(fn () => $this->record->status === TenderStatus::SUBMITTED)
                ->form([
                    \Filament\Forms\Components\Section::make('نتائج الفتح')
                        ->schema([
                            \Filament\Forms\Components\DateTimePicker::make('opening_date')
                                ->label('تاريخ الفتح')
                                ->required(),
                            \Filament\Forms\Components\TextInput::make('participants_count')
                                ->label('عدد المتنافسين')
                                ->numeric(),
                            \Filament\Forms\Components\TextInput::make('our_rank')
                                ->label('ترتيبنا')
                                ->numeric(),
                            \Filament\Forms\Components\TextInput::make('lowest_price')
                                ->label('أقل سعر')
                                ->numeric()
                                ->prefix('JOD'),
                            \Filament\Forms\Components\TextInput::make('highest_price')
                                ->label('أعلى سعر')
                                ->numeric()
                                ->prefix('JOD'),
                        ])
                        ->columns(2),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'status' => TenderStatus::OPENING,
                        'opening_date' => $data['opening_date'],
                        'our_rank' => $data['our_rank'],
                    ]);
                    $this->createStageLog('opening', 'تم تسجيل نتائج الفتح - الترتيب: ' . $data['our_rank']);
                    Notification::make()->title('تم تسجيل نتائج الفتح')->success()->send();
                }),

            // ===== المرحلة 6: الترسية =====
            Actions\Action::make('record_result')
                ->label('تسجيل النتيجة النهائية')
                ->icon('heroicon-o-trophy')
                ->color('warning')
                ->visible(fn () => $this->record->status === TenderStatus::OPENING)
                ->form([
                    \Filament\Forms\Components\Section::make('النتيجة النهائية')
                        ->schema([
                            \Filament\Forms\Components\Radio::make('result')
                                ->label('النتيجة')
                                ->options([
                                    'won' => '🏆 فوز',
                                    'lost' => '❌ خسارة',
                                    'cancelled' => '🚫 إلغاء العطاء',
                                ])
                                ->required()
                                ->inline(),
                            \Filament\Forms\Components\DatePicker::make('award_date')
                                ->label('تاريخ الترسية')
                                ->visible(fn ($get) => $get('result') === 'won'),
                            \Filament\Forms\Components\TextInput::make('winner_name')
                                ->label('اسم الفائز')
                                ->visible(fn ($get) => $get('result') === 'lost'),
                            \Filament\Forms\Components\TextInput::make('winning_price')
                                ->label('السعر الفائز')
                                ->numeric()
                                ->prefix('JOD')
                                ->visible(fn ($get) => in_array($get('result'), ['won', 'lost'])),
                            \Filament\Forms\Components\Textarea::make('loss_reason')
                                ->label('سبب الخسارة')
                                ->rows(2)
                                ->visible(fn ($get) => $get('result') === 'lost'),
                            \Filament\Forms\Components\Textarea::make('lessons_learned')
                                ->label('الدروس المستفادة')
                                ->rows(3),
                        ])
                        ->columns(2),
                ])
                ->action(function (array $data) {
                    $newStatus = match($data['result']) {
                        'won' => TenderStatus::WON,
                        'lost' => TenderStatus::LOST,
                        default => TenderStatus::CANCELLED,
                    };
                    
                    $this->record->update([
                        'status' => $newStatus,
                        'result' => $data['result'],
                        'award_date' => $data['award_date'] ?? null,
                        'winner_name' => $data['winner_name'] ?? null,
                        'winning_price' => $data['winning_price'] ?? null,
                        'loss_reason' => $data['loss_reason'] ?? null,
                        'lessons_learned' => $data['lessons_learned'] ?? null,
                    ]);
                    
                    $msg = match($data['result']) {
                        'won' => '🎉 مبروك! تم الفوز بالعطاء',
                        'lost' => 'تم تسجيل خسارة العطاء',
                        default => 'تم إلغاء العطاء',
                    };
                    $this->createStageLog($newStatus->value, $msg);
                    Notification::make()->title($msg)->success()->send();
                }),

            // تحويل لمشروع
            Actions\Action::make('convert_to_project')
                ->label('تحويل لمشروع')
                ->icon('heroicon-o-building-office-2')
                ->color('success')
                ->visible(fn () => $this->record->status === TenderStatus::WON && !$this->record->contract_id)
                ->url(fn () => route('filament.admin.resources.projects.create', ['tender_id' => $this->record->id])),

            // إجراءات إضافية
            Actions\ActionGroup::make([
                Actions\Action::make('print_summary')
                    ->label('طباعة ملخص')
                    ->icon('heroicon-o-printer')
                    ->action(function () {
                        // TODO: Implement print functionality
                        Notification::make()
                            ->title('قريباً')
                            ->body('سيتم تفعيل خاصية الطباعة قريباً')
                            ->info()
                            ->send();
                    }),
                Actions\Action::make('duplicate')
                    ->label('نسخ العطاء')
                    ->icon('heroicon-o-document-duplicate')
                    ->requiresConfirmation()
                    ->action(function () {
                        $new = $this->record->replicate();
                        $new->tender_number = 'T-' . date('Y') . '-' . str_pad(rand(1,999), 3, '0', STR_PAD_LEFT);
                        $new->status = TenderStatus::NEW;
                        $new->save();
                        Notification::make()->title('تم نسخ العطاء')->success()->send();
                    }),
                Actions\Action::make('archive')
                    ->label('أرشفة')
                    ->icon('heroicon-o-archive-box')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn () => in_array($this->record->status, [TenderStatus::NO_GO, TenderStatus::LOST, TenderStatus::CANCELLED])),
            ])
            ->label('المزيد')
            ->icon('heroicon-o-ellipsis-vertical')
            ->color('gray'),
        ];
    }

    protected function createStageLog(string $stage, string $notes): void
    {
        $stageOrder = match($stage) {
            'discovery', 'الرصد' => 1,
            'studying', 'الدراسة' => 2,
            'go', 'قرار المضي' => 3,
            'preparation', 'الإعداد' => 4,
            'submission', 'التقديم' => 5,
            'opening', 'الفتح' => 6,
            'awarded', 'won', 'الترسية' => 7,
            default => 0,
        };
        
        $this->record->stageLogs()->create([
            'stage' => $stage,
            'stage_order' => $stageOrder,
            'notes' => $notes,
            'user_id' => auth()->id(),
            'created_at' => now(),
        ]);
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // ===== شريط التقدم =====
                Infolists\Components\Section::make()
                    ->schema([
                        Infolists\Components\ViewEntry::make('progress')
                            ->label('')
                            ->view('filament.infolists.components.tender-progress'),
                    ])
                    ->columnSpanFull(),

                // ===== ملخص سريع =====
                Infolists\Components\Section::make()
                    ->schema([
                        Infolists\Components\Grid::make(5)
                            ->schema([
                                Infolists\Components\TextEntry::make('days_until_submission')
                                    ->label('الأيام المتبقية')
                                    ->size('lg')
                                    ->weight('bold')
                                    ->badge()
                                    ->color(fn ($state) => $state === null ? 'gray' : ($state < 0 ? 'danger' : ($state <= 7 ? 'warning' : 'success'))),
                                Infolists\Components\TextEntry::make('estimated_value')
                                    ->label('القيمة التقديرية')
                                    ->money('JOD')
                                    ->size('lg'),
                                Infolists\Components\TextEntry::make('submitted_price')
                                    ->label('السعر المقدم')
                                    ->money('JOD')
                                    ->size('lg')
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('boqItems_count')
                                    ->label('بنود BOQ')
                                    ->state(fn ($record) => $record->boqItems()->count())
                                    ->badge(),
                                Infolists\Components\TextEntry::make('documents_count')
                                    ->label('الوثائق')
                                    ->state(fn ($record) => $record->documents()->count())
                                    ->badge(),
                            ]),
                    ])
                    ->columnSpanFull(),

                // ===== التبويبات الستة المحسّنة =====
                Tabs::make('TenderTabs')
                    ->tabs([
                        // ========== المرحلة 1: الرصد والتسجيل ==========
                        Tabs\Tab::make('الرصد والتسجيل')
                            ->icon('heroicon-o-magnifying-glass')
                            ->badge(fn ($record) => $record->status === TenderStatus::NEW ? 'جديد' : null)
                            ->badgeColor('info')
                            ->schema([
                                Infolists\Components\Grid::make(2)
                                    ->schema([
                                        // العمود الأيسر
                                        Infolists\Components\Section::make('بيانات العطاء')
                                            ->icon('heroicon-o-document-text')
                                            ->schema([
                                                Infolists\Components\Grid::make(2)
                                                    ->schema([
                                                        Infolists\Components\TextEntry::make('tender_number')
                                                            ->label('رقم العطاء')
                                                            ->copyable()
                                                            ->weight('bold'),
                                                        Infolists\Components\TextEntry::make('reference_number')
                                                            ->label('الرقم المرجعي')
                                                            ->copyable(),
                                                    ]),
                                                Infolists\Components\TextEntry::make('name_ar')
                                                    ->label('اسم العطاء')
                                                    ->columnSpanFull()
                                                    ->weight('bold'),
                                                Infolists\Components\Grid::make(2)
                                                    ->schema([
                                                        Infolists\Components\TextEntry::make('tender_type')
                                                            ->label('نوع العطاء')
                                                            ->badge(),
                                                        Infolists\Components\TextEntry::make('tender_method')
                                                            ->label('أسلوب الطرح')
                                                            ->badge(),
                                                    ]),
                                                Infolists\Components\TextEntry::make('description')
                                                    ->label('الوصف')
                                                    ->columnSpanFull()
                                                    ->markdown(),
                                            ]),

                                        // العمود الأيمن
                                        Infolists\Components\Group::make([
                                            Infolists\Components\Section::make('الجهة المالكة')
                                                ->icon('heroicon-o-building-library')
                                                ->schema([
                                                    Infolists\Components\TextEntry::make('owner_name')
                                                        ->label('اسم الجهة')
                                                        ->weight('bold'),
                                                    Infolists\Components\TextEntry::make('owner_contact_person')
                                                        ->label('جهة الاتصال'),
                                                    Infolists\Components\Grid::make(2)
                                                        ->schema([
                                                            Infolists\Components\TextEntry::make('owner_phone')
                                                                ->label('الهاتف')
                                                                ->icon('heroicon-o-phone'),
                                                            Infolists\Components\TextEntry::make('owner_email')
                                                                ->label('البريد')
                                                                ->icon('heroicon-o-envelope'),
                                                        ]),
                                                ]),
                                            Infolists\Components\Section::make('التواريخ الهامة')
                                                ->icon('heroicon-o-calendar')
                                                ->schema([
                                                    Infolists\Components\Grid::make(2)
                                                        ->schema([
                                                            Infolists\Components\TextEntry::make('publication_date')
                                                                ->label('تاريخ الإعلان')
                                                                ->date(),
                                                            Infolists\Components\TextEntry::make('documents_sale_end')
                                                                ->label('شراء الوثائق حتى')
                                                                ->date(),
                                                            Infolists\Components\TextEntry::make('site_visit_date')
                                                                ->label('زيارة الموقع')
                                                                ->date(),
                                                            Infolists\Components\TextEntry::make('questions_deadline')
                                                                ->label('آخر موعد للاستفسارات')
                                                                ->date(),
                                                        ]),
                                                    Infolists\Components\TextEntry::make('submission_deadline')
                                                        ->label('موعد التقديم')
                                                        ->dateTime()
                                                        ->weight('bold')
                                                        ->color('danger'),
                                                ]),
                                        ]),
                                    ]),
                            ]),

                        // ========== المرحلة 2: الدراسة والقرار ==========
                        Tabs\Tab::make('الدراسة والقرار')
                            ->icon('heroicon-o-clipboard-document-check')
                            ->badge(fn ($record) => $record->decision ? ($record->decision === 'go' ? 'Go' : 'No-Go') : null)
                            ->badgeColor(fn ($record) => $record->decision === 'go' ? 'success' : 'danger')
                            ->schema([
                                Infolists\Components\Grid::make(2)
                                    ->schema([
                                        // قرار المشاركة
                                        Infolists\Components\Section::make('قرار المشاركة')
                                            ->icon('heroicon-o-scale')
                                            ->schema([
                                                Infolists\Components\Grid::make(3)
                                                    ->schema([
                                                        Infolists\Components\TextEntry::make('decision')
                                                            ->label('القرار')
                                                            ->badge()
                                                            ->size('lg')
                                                            ->formatStateUsing(fn ($state) => $state === 'go' ? '✅ Go' : ($state === 'no_go' ? '❌ No-Go' : '⏳ قيد الدراسة'))
                                                            ->color(fn ($state) => $state === 'go' ? 'success' : ($state === 'no_go' ? 'danger' : 'warning')),
                                                        Infolists\Components\TextEntry::make('decision_date')
                                                            ->label('تاريخ القرار')
                                                            ->date(),
                                                        Infolists\Components\TextEntry::make('decisionBy.name')
                                                            ->label('بواسطة'),
                                                    ]),
                                                Infolists\Components\TextEntry::make('decision_notes')
                                                    ->label('مبررات القرار')
                                                    ->columnSpanFull()
                                                    ->markdown(),
                                            ]),

                                        // المتطلبات والتأهيل
                                        Infolists\Components\Section::make('متطلبات التأهيل')
                                            ->icon('heroicon-o-clipboard-document-list')
                                            ->schema([
                                                Infolists\Components\Grid::make(2)
                                                    ->schema([
                                                        Infolists\Components\TextEntry::make('required_classification')
                                                            ->label('التصنيف المطلوب')
                                                            ->badge(),
                                                        Infolists\Components\TextEntry::make('minimum_experience_years')
                                                            ->label('الخبرة (سنوات)')
                                                            ->suffix(' سنة'),
                                                        Infolists\Components\TextEntry::make('minimum_similar_projects')
                                                            ->label('المشاريع المماثلة')
                                                            ->suffix(' مشروع'),
                                                        Infolists\Components\TextEntry::make('minimum_project_value')
                                                            ->label('الحد الأدنى للقيمة')
                                                            ->money('JOD'),
                                                    ]),
                                                Infolists\Components\TextEntry::make('technical_requirements')
                                                    ->label('المتطلبات الفنية')
                                                    ->columnSpanFull()
                                                    ->markdown(),
                                            ]),
                                    ]),

                                // الكفالات المطلوبة
                                Infolists\Components\Section::make('الكفالات والضمانات')
                                    ->icon('heroicon-o-banknotes')
                                    ->schema([
                                        Infolists\Components\Grid::make(4)
                                            ->schema([
                                                Infolists\Components\TextEntry::make('bid_bond_percentage')
                                                    ->label('كفالة العطاء')
                                                    ->suffix('%'),
                                                Infolists\Components\TextEntry::make('performance_bond_percentage')
                                                    ->label('كفالة الأداء')
                                                    ->suffix('%'),
                                                Infolists\Components\TextEntry::make('advance_payment_percentage')
                                                    ->label('الدفعة المقدمة')
                                                    ->suffix('%'),
                                                Infolists\Components\TextEntry::make('retention_percentage')
                                                    ->label('المحتجزات')
                                                    ->suffix('%'),
                                            ]),
                                    ])
                                    ->collapsible(),
                            ]),

                        // ========== المرحلة 3: إعداد العرض ==========
                        Tabs\Tab::make('إعداد العرض')
                            ->icon('heroicon-o-document-text')
                            ->badge(fn ($record) => $record->boqItems()->count() ?: null)
                            ->badgeColor('primary')
                            ->schema([
                                // ملخص التسعير
                                Infolists\Components\Section::make('ملخص التسعير')
                                    ->icon('heroicon-o-calculator')
                                    ->schema([
                                        Infolists\Components\Grid::make(5)
                                            ->schema([
                                                Infolists\Components\TextEntry::make('total_direct_cost')
                                                    ->label('التكاليف المباشرة')
                                                    ->money('JOD')
                                                    ->color('info'),
                                                Infolists\Components\TextEntry::make('total_overhead')
                                                    ->label('المصاريف العمومية')
                                                    ->money('JOD'),
                                                Infolists\Components\TextEntry::make('total_cost')
                                                    ->label('إجمالي التكلفة')
                                                    ->money('JOD')
                                                    ->weight('bold'),
                                                Infolists\Components\TextEntry::make('markup_percentage')
                                                    ->label('نسبة الربح')
                                                    ->suffix('%')
                                                    ->badge()
                                                    ->color('success'),
                                                Infolists\Components\TextEntry::make('submitted_price')
                                                    ->label('السعر المقدم')
                                                    ->money('JOD')
                                                    ->weight('bold')
                                                    ->size('lg')
                                                    ->color('success'),
                                            ]),
                                    ]),

                                // جدول الكميات (أول 10 بنود)
                                Infolists\Components\Section::make('جدول الكميات')
                                    ->icon('heroicon-o-table-cells')
                                    ->schema([
                                        Infolists\Components\RepeatableEntry::make('boqItems')
                                            ->label('')
                                            ->schema([
                                                Infolists\Components\TextEntry::make('item_number')
                                                    ->label('رقم'),
                                                Infolists\Components\TextEntry::make('description')
                                                    ->label('الوصف')
                                                    ->limit(50),
                                                Infolists\Components\TextEntry::make('quantity')
                                                    ->label('الكمية'),
                                                Infolists\Components\TextEntry::make('unit')
                                                    ->label('الوحدة'),
                                                Infolists\Components\TextEntry::make('unit_price')
                                                    ->label('سعر الوحدة')
                                                    ->money('JOD'),
                                                Infolists\Components\TextEntry::make('total_price')
                                                    ->label('الإجمالي')
                                                    ->money('JOD')
                                                    ->weight('bold'),
                                            ])
                                            ->columns(6)
                                            ->columnSpanFull(),
                                    ])
                                    ->collapsible(),
                            ]),

                        // ========== المرحلة 4: التقديم ==========
                        Tabs\Tab::make('التقديم')
                            ->icon('heroicon-o-paper-airplane')
                            ->badge(fn ($record) => $record->status === TenderStatus::SUBMITTED ? '✓' : null)
                            ->badgeColor('success')
                            ->schema([
                                Infolists\Components\Grid::make(2)
                                    ->schema([
                                        Infolists\Components\Section::make('بيانات التقديم')
                                            ->icon('heroicon-o-paper-airplane')
                                            ->schema([
                                                Infolists\Components\Grid::make(2)
                                                    ->schema([
                                                        Infolists\Components\TextEntry::make('submission_date')
                                                            ->label('تاريخ ووقت التقديم')
                                                            ->dateTime()
                                                            ->weight('bold'),
                                                        Infolists\Components\TextEntry::make('submission_method')
                                                            ->label('طريقة التقديم')
                                                            ->badge(),
                                                        Infolists\Components\TextEntry::make('receipt_number')
                                                            ->label('رقم الإيصال')
                                                            ->copyable(),
                                                        Infolists\Components\TextEntry::make('submittedBy.name')
                                                            ->label('مقدم بواسطة'),
                                                    ]),
                                            ]),

                                        Infolists\Components\Section::make('الكفالة الابتدائية')
                                            ->icon('heroicon-o-banknotes')
                                            ->schema([
                                                Infolists\Components\Grid::make(2)
                                                    ->schema([
                                                        Infolists\Components\TextEntry::make('bid_bond_type')
                                                            ->label('نوع الكفالة')
                                                            ->badge(),
                                                        Infolists\Components\TextEntry::make('bid_bond_amount')
                                                            ->label('مبلغ الكفالة')
                                                            ->money('JOD')
                                                            ->weight('bold'),
                                                    ]),
                                            ]),
                                    ]),
                            ]),

                        // ========== المرحلة 5: الفتح والنتائج ==========
                        Tabs\Tab::make('الفتح والنتائج')
                            ->icon('heroicon-o-chart-bar')
                            ->badge(fn ($record) => $record->our_rank ? '#' . $record->our_rank : null)
                            ->badgeColor(fn ($record) => $record->our_rank == 1 ? 'success' : 'warning')
                            ->schema([
                                Infolists\Components\Grid::make(2)
                                    ->schema([
                                        Infolists\Components\Section::make('نتائج الفتح')
                                            ->icon('heroicon-o-envelope-open')
                                            ->schema([
                                                Infolists\Components\Grid::make(2)
                                                    ->schema([
                                                        Infolists\Components\TextEntry::make('opening_date')
                                                            ->label('تاريخ الفتح')
                                                            ->dateTime(),
                                                        Infolists\Components\TextEntry::make('our_rank')
                                                            ->label('ترتيبنا')
                                                            ->badge()
                                                            ->size('lg')
                                                            ->color(fn ($state) => $state == 1 ? 'success' : ($state <= 3 ? 'warning' : 'gray')),
                                                    ]),
                                                Infolists\Components\TextEntry::make('submitted_price')
                                                    ->label('سعرنا المقدم')
                                                    ->money('JOD')
                                                    ->weight('bold'),
                                            ]),

                                        Infolists\Components\Section::make('الفائز')
                                            ->icon('heroicon-o-trophy')
                                            ->visible(fn ($record) => $record->winner_name)
                                            ->schema([
                                                Infolists\Components\TextEntry::make('winner_name')
                                                    ->label('اسم الفائز')
                                                    ->weight('bold'),
                                                Infolists\Components\TextEntry::make('winning_price')
                                                    ->label('السعر الفائز')
                                                    ->money('JOD'),
                                            ]),
                                    ]),

                                // جدول المنافسين
                                Infolists\Components\Section::make('المنافسون')
                                    ->icon('heroicon-o-users')
                                    ->schema([
                                        Infolists\Components\RepeatableEntry::make('competitors')
                                            ->label('')
                                            ->schema([
                                                Infolists\Components\TextEntry::make('rank')
                                                    ->label('الترتيب')
                                                    ->badge(),
                                                Infolists\Components\TextEntry::make('company_name')
                                                    ->label('اسم الشركة'),
                                                Infolists\Components\TextEntry::make('submitted_price')
                                                    ->label('السعر المقدم')
                                                    ->money('JOD'),
                                                Infolists\Components\TextEntry::make('notes')
                                                    ->label('ملاحظات'),
                                            ])
                                            ->columns(4)
                                            ->columnSpanFull(),
                                    ])
                                    ->collapsible(),
                            ]),

                        // ========== المرحلة 6: الترسية والتحويل ==========
                        Tabs\Tab::make('الترسية والتحويل')
                            ->icon('heroicon-o-trophy')
                            ->badge(fn ($record) => $record->result?->value === 'won' ? '🏆' : null)
                            ->badgeColor('success')
                            ->schema([
                                // النتيجة النهائية
                                Infolists\Components\Section::make('النتيجة النهائية')
                                    ->icon('heroicon-o-flag')
                                    ->schema([
                                        Infolists\Components\Grid::make(3)
                                            ->schema([
                                                Infolists\Components\TextEntry::make('result')
                                                    ->label('النتيجة')
                                                    ->badge()
                                                    ->size('lg')
                                                    ->formatStateUsing(fn ($state) => match($state?->value) {
                                                        'won' => '🏆 فوز',
                                                        'lost' => '❌ خسارة',
                                                        'cancelled' => '🚫 ملغي',
                                                        default => '⏳ بانتظار النتيجة',
                                                    })
                                                    ->color(fn ($state) => match($state?->value) {
                                                        'won' => 'success',
                                                        'lost' => 'danger',
                                                        default => 'gray',
                                                    }),
                                                Infolists\Components\TextEntry::make('award_date')
                                                    ->label('تاريخ الترسية')
                                                    ->date(),
                                                Infolists\Components\TextEntry::make('winning_price')
                                                    ->label('قيمة العقد')
                                                    ->money('JOD')
                                                    ->weight('bold')
                                                    ->visible(fn ($record) => $record->result?->value === 'won'),
                                            ]),
                                    ]),

                                // في حالة الخسارة
                                Infolists\Components\Section::make('تحليل الخسارة')
                                    ->icon('heroicon-o-chart-pie')
                                    ->visible(fn ($record) => $record->result?->value === 'lost')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('loss_reason')
                                            ->label('سبب الخسارة')
                                            ->columnSpanFull(),
                                        Infolists\Components\Grid::make(2)
                                            ->schema([
                                                Infolists\Components\TextEntry::make('winner_name')
                                                    ->label('الفائز'),
                                                Infolists\Components\TextEntry::make('price_difference')
                                                    ->label('فرق السعر')
                                                    ->state(fn ($record) => $record->winning_price && $record->submitted_price 
                                                        ? number_format($record->submitted_price - $record->winning_price, 2) . ' JOD'
                                                        : '-'),
                                            ]),
                                    ]),

                                // الدروس المستفادة
                                Infolists\Components\Section::make('الدروس المستفادة')
                                    ->icon('heroicon-o-light-bulb')
                                    ->visible(fn ($record) => $record->lessons_learned)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('lessons_learned')
                                            ->label('')
                                            ->markdown()
                                            ->columnSpanFull(),
                                    ]),

                                // رابط المشروع/العقد
                                Infolists\Components\Section::make('المشروع/العقد')
                                    ->icon('heroicon-o-building-office-2')
                                    ->visible(fn ($record) => $record->contract_id || $record->result?->value === 'won')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('contract.contract_number')
                                            ->label('رقم العقد')
                                            ->url(fn ($record) => $record->contract_id 
                                                ? route('filament.admin.resources.contracts.view', $record->contract_id) 
                                                : null)
                                            ->color('primary'),
                                    ]),
                            ]),

                        // ========== المتطلبات الأردنية ==========
                        Tabs\Tab::make('المتطلبات الأردنية')
                            ->icon('heroicon-o-flag')
                            ->badge(fn ($record) => $record->allows_price_preferences ? 'أفضليات' : null)
                            ->badgeColor('info')
                            ->schema([
                                // التصنيف والتخصص
                                Infolists\Components\Section::make('التصنيف والتخصص المطلوب')
                                    ->icon('heroicon-o-academic-cap')
                                    ->schema([
                                        Infolists\Components\Grid::make(4)
                                            ->schema([
                                                Infolists\Components\TextEntry::make('classification_field')
                                                    ->label('حقل التصنيف')
                                                    ->placeholder('غير محدد'),
                                                Infolists\Components\TextEntry::make('classification_specialty')
                                                    ->label('التخصص')
                                                    ->placeholder('غير محدد'),
                                                Infolists\Components\TextEntry::make('classification_category')
                                                    ->label('الفئة')
                                                    ->badge()
                                                    ->placeholder('غير محدد'),
                                                Infolists\Components\TextEntry::make('classification_scope')
                                                    ->label('النطاق المالي')
                                                    ->placeholder('غير محدد'),
                                            ]),
                                    ])
                                    ->collapsible(),

                                // فترة الاعتراض
                                Infolists\Components\Section::make('فترة الاعتراض')
                                    ->icon('heroicon-o-clock')
                                    ->description('الفترة المتاحة للاعتراض على الإحالة الأولية')
                                    ->schema([
                                        Infolists\Components\Grid::make(4)
                                            ->schema([
                                                Infolists\Components\TextEntry::make('objection_period_days')
                                                    ->label('مدة الفترة')
                                                    ->suffix(' يوم')
                                                    ->badge()
                                                    ->color('warning'),
                                                Infolists\Components\TextEntry::make('objection_period_start')
                                                    ->label('بداية الفترة')
                                                    ->date()
                                                    ->placeholder('غير محدد'),
                                                Infolists\Components\TextEntry::make('objection_period_end')
                                                    ->label('نهاية الفترة')
                                                    ->date()
                                                    ->placeholder('غير محدد'),
                                                Infolists\Components\TextEntry::make('objection_fee')
                                                    ->label('رسم الاعتراض')
                                                    ->money('JOD')
                                                    ->placeholder('500 د.أ'),
                                            ]),
                                    ])
                                    ->collapsible(),

                                // اجتماع ما قبل تقديم العطاءات
                                Infolists\Components\Section::make('اجتماع ما قبل تقديم العطاءات')
                                    ->icon('heroicon-o-user-group')
                                    ->schema([
                                        Infolists\Components\Grid::make(3)
                                            ->schema([
                                                Infolists\Components\IconEntry::make('pre_bid_meeting_required')
                                                    ->label('الاجتماع مطلوب')
                                                    ->boolean(),
                                                Infolists\Components\TextEntry::make('pre_bid_meeting_date')
                                                    ->label('موعد الاجتماع')
                                                    ->dateTime()
                                                    ->placeholder('غير محدد'),
                                                Infolists\Components\TextEntry::make('pre_bid_meeting_location')
                                                    ->label('مكان الاجتماع')
                                                    ->placeholder('غير محدد'),
                                            ]),
                                        Infolists\Components\TextEntry::make('pre_bid_meeting_minutes')
                                            ->label('محضر الاجتماع')
                                            ->markdown()
                                            ->columnSpanFull()
                                            ->visible(fn ($record) => $record->pre_bid_meeting_minutes),
                                    ])
                                    ->collapsible()
                                    ->collapsed(fn ($record) => !$record->pre_bid_meeting_required),

                                Infolists\Components\Grid::make(2)
                                    ->schema([
                                        // الأفضليات السعرية
                                        Infolists\Components\Section::make('الأفضليات السعرية')
                                            ->icon('heroicon-o-receipt-percent')
                                            ->description('حسب قرارات مجلس الوزراء')
                                            ->schema([
                                                Infolists\Components\IconEntry::make('allows_price_preferences')
                                                    ->label('تسمح بالأفضليات السعرية')
                                                    ->boolean(),
                                                Infolists\Components\TextEntry::make('sme_preference_percentage')
                                                    ->label('نسبة أفضلية SME')
                                                    ->suffix('%')
                                                    ->badge()
                                                    ->color('success')
                                                    ->visible(fn ($record) => $record->allows_price_preferences),
                                                Infolists\Components\IconEntry::make('local_products_preference')
                                                    ->label('أفضلية للمنتجات المحلية')
                                                    ->boolean()
                                                    ->visible(fn ($record) => $record->allows_price_preferences),
                                            ])
                                            ->collapsible(),

                                        // المقاولين الفرعيين
                                        Infolists\Components\Section::make('المقاولين الفرعيين')
                                            ->icon('heroicon-o-users')
                                            ->schema([
                                                Infolists\Components\IconEntry::make('allows_subcontracting')
                                                    ->label('يسمح بالتعاقد الفرعي')
                                                    ->boolean(),
                                                Infolists\Components\TextEntry::make('max_subcontracting_percentage')
                                                    ->label('الحد الأقصى للتعاقد الفرعي')
                                                    ->suffix('%')
                                                    ->badge()
                                                    ->color('warning')
                                                    ->visible(fn ($record) => $record->allows_subcontracting),
                                                Infolists\Components\TextEntry::make('local_subcontractor_percentage')
                                                    ->label('الحد الأدنى للمحليين')
                                                    ->suffix('%')
                                                    ->badge()
                                                    ->color('info')
                                                    ->visible(fn ($record) => $record->allows_subcontracting),
                                            ])
                                            ->collapsible(),
                                    ]),

                                Infolists\Components\Grid::make(2)
                                    ->schema([
                                        // الائتلافات
                                        Infolists\Components\Section::make('الائتلافات')
                                            ->icon('heroicon-o-user-group')
                                            ->schema([
                                                Infolists\Components\IconEntry::make('allows_consortium')
                                                    ->label('يسمح بالائتلافات')
                                                    ->boolean(),
                                                Infolists\Components\TextEntry::make('max_consortium_members')
                                                    ->label('الحد الأقصى لأعضاء الائتلاف')
                                                    ->badge()
                                                    ->visible(fn ($record) => $record->allows_consortium),
                                            ])
                                            ->collapsible(),

                                        // الإقرارات المطلوبة
                                        Infolists\Components\Section::make('الإقرارات المطلوبة')
                                            ->icon('heroicon-o-document-check')
                                            ->schema([
                                                Infolists\Components\Grid::make(2)
                                                    ->schema([
                                                        Infolists\Components\IconEntry::make('esmp_required')
                                                            ->label('خطة ESMP')
                                                            ->boolean(),
                                                        Infolists\Components\IconEntry::make('code_of_conduct_required')
                                                            ->label('قواعد السلوك')
                                                            ->boolean(),
                                                        Infolists\Components\IconEntry::make('anti_corruption_declaration_required')
                                                            ->label('مكافحة الفساد')
                                                            ->boolean(),
                                                        Infolists\Components\IconEntry::make('conflict_of_interest_declaration_required')
                                                            ->label('عدم تضارب المصالح')
                                                            ->boolean(),
                                                    ]),
                                            ])
                                            ->collapsible(),
                                    ]),

                                // معايير التقييم
                                Infolists\Components\Section::make('معايير التقييم')
                                    ->icon('heroicon-o-chart-bar')
                                    ->schema([
                                        Infolists\Components\Grid::make(3)
                                            ->schema([
                                                Infolists\Components\TextEntry::make('technical_pass_score')
                                                    ->label('درجة النجاح الفني')
                                                    ->suffix('%')
                                                    ->badge()
                                                    ->color('primary'),
                                                Infolists\Components\TextEntry::make('technical_weight')
                                                    ->label('وزن التقييم الفني')
                                                    ->suffix('%')
                                                    ->badge(),
                                                Infolists\Components\TextEntry::make('financial_weight')
                                                    ->label('وزن التقييم المالي')
                                                    ->suffix('%')
                                                    ->badge(),
                                            ]),
                                    ])
                                    ->collapsible(),

                                // التصحيحات الحسابية
                                Infolists\Components\Section::make('التصحيحات الحسابية')
                                    ->icon('heroicon-o-calculator')
                                    ->schema([
                                        Infolists\Components\Grid::make(2)
                                            ->schema([
                                                Infolists\Components\IconEntry::make('allow_arithmetic_corrections')
                                                    ->label('السماح بالتصحيحات الحسابية')
                                                    ->boolean(),
                                                Infolists\Components\IconEntry::make('words_over_numbers_precedence')
                                                    ->label('أولوية الكلمات على الأرقام')
                                                    ->boolean(),
                                            ]),
                                    ])
                                    ->collapsible(),

                                // إحصائيات العلاقات
                                Infolists\Components\Section::make('إحصائيات')
                                    ->icon('heroicon-o-chart-pie')
                                    ->schema([
                                        Infolists\Components\Grid::make(5)
                                            ->schema([
                                                Infolists\Components\TextEntry::make('objections_count')
                                                    ->label('الاعتراضات')
                                                    ->state(fn ($record) => $record->objections()->count())
                                                    ->badge()
                                                    ->color('warning'),
                                                Infolists\Components\TextEntry::make('declarations_count')
                                                    ->label('الإقرارات')
                                                    ->state(fn ($record) => $record->declarations()->count())
                                                    ->badge()
                                                    ->color('info'),
                                                Infolists\Components\TextEntry::make('consortiums_count')
                                                    ->label('الائتلافات')
                                                    ->state(fn ($record) => $record->consortiums()->count())
                                                    ->badge()
                                                    ->color('primary'),
                                                Infolists\Components\TextEntry::make('price_preferences_count')
                                                    ->label('الأفضليات السعرية')
                                                    ->state(fn ($record) => $record->pricePreferences()->count())
                                                    ->badge()
                                                    ->color('success'),
                                                Infolists\Components\TextEntry::make('corrections_count')
                                                    ->label('التصحيحات الحسابية')
                                                    ->state(fn ($record) => $record->arithmeticCorrections()->count())
                                                    ->badge()
                                                    ->color('gray'),
                                            ]),
                                    ])
                                    ->collapsible(),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->persistTabInQueryString(),
            ]);
    }
}
