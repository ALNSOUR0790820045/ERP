<?php

namespace App\Filament\Resources\TenderResource\Pages;

use App\Enums\TenderStatus;
use App\Filament\Resources\TenderResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Components\Tabs;

class ViewTender extends ViewRecord
{
    protected static string $resource = TenderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('تعديل'),
            
            // إجراءات سريعة حسب المرحلة
            Actions\Action::make('go_no_go')
                ->label('قرار Go/No-Go')
                ->icon('heroicon-o-check-circle')
                ->color('warning')
                ->visible(fn () => in_array($this->record->status, [TenderStatus::NEW, TenderStatus::STUDYING]))
                ->form([
                    \Filament\Forms\Components\Select::make('decision')
                        ->label('القرار')
                        ->options([
                            'go' => 'Go - المشاركة',
                            'no_go' => 'No-Go - عدم المشاركة',
                        ])
                        ->required(),
                    \Filament\Forms\Components\Textarea::make('reason')
                        ->label('السبب')
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'decision' => $data['decision'],
                        'decision_notes' => $data['reason'],
                        'decision_date' => now(),
                        'status' => $data['decision'] === 'go' ? TenderStatus::PRICING : TenderStatus::NO_GO,
                    ]);
                }),
            
            Actions\Action::make('submit_tender')
                ->label('تقديم العطاء')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->visible(fn () => in_array($this->record->status, [TenderStatus::PRICING, TenderStatus::READY]))
                ->requiresConfirmation()
                ->modalHeading('تأكيد تقديم العطاء')
                ->modalDescription('هل أنت متأكد من تقديم العطاء؟')
                ->action(function () {
                    $this->record->update([
                        'status' => TenderStatus::SUBMITTED,
                        'submission_date' => now(),
                    ]);
                }),
            
            Actions\Action::make('convert_to_project')
                ->label('تحويل لمشروع')
                ->icon('heroicon-o-arrow-right-circle')
                ->color('success')
                ->visible(fn () => $this->record->result?->value === 'won')
                ->url(fn () => route('filament.admin.resources.projects.create', ['tender_id' => $this->record->id])),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // شريط التقدم
                Infolists\Components\Section::make()
                    ->schema([
                        Infolists\Components\ViewEntry::make('progress')
                            ->label('')
                            ->view('filament.infolists.components.tender-progress'),
                    ])
                    ->columnSpanFull(),

                // التبويبات الستة
                Tabs::make('TenderTabs')
                    ->tabs([
                        // المرحلة 1: الرصد والتسجيل
                        Tabs\Tab::make('الرصد والتسجيل')
                            ->icon('heroicon-o-magnifying-glass')
                            ->schema([
                                Infolists\Components\Grid::make(3)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('tender_number')
                                            ->label('رقم العطاء'),
                                        Infolists\Components\TextEntry::make('reference_number')
                                            ->label('الرقم المرجعي'),
                                        Infolists\Components\TextEntry::make('status')
                                            ->label('الحالة')
                                            ->badge(),
                                    ]),
                                Infolists\Components\TextEntry::make('name_ar')
                                    ->label('اسم العطاء')
                                    ->columnSpanFull(),
                                Infolists\Components\Grid::make(3)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('tender_type')
                                            ->label('نوع العطاء')
                                            ->badge(),
                                        Infolists\Components\TextEntry::make('tender_method')
                                            ->label('أسلوب الطرح')
                                            ->badge(),
                                        Infolists\Components\TextEntry::make('owner.name_ar')
                                            ->label('المالك'),
                                    ]),
                                Infolists\Components\Grid::make(3)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('estimated_value')
                                            ->label('القيمة التقديرية')
                                            ->money('JOD'),
                                        Infolists\Components\TextEntry::make('submission_deadline')
                                            ->label('موعد التقديم')
                                            ->dateTime(),
                                        Infolists\Components\TextEntry::make('days_until_submission')
                                            ->label('الأيام المتبقية')
                                            ->badge()
                                            ->color(fn ($state) => $state < 0 ? 'danger' : ($state <= 7 ? 'warning' : 'success')),
                                    ]),
                            ]),

                        // المرحلة 2: الدراسة والقرار
                        Tabs\Tab::make('الدراسة والقرار')
                            ->icon('heroicon-o-clipboard-document-check')
                            ->schema([
                                Infolists\Components\Section::make('قرار المشاركة')
                                    ->schema([
                                        Infolists\Components\Grid::make(3)
                                            ->schema([
                                                Infolists\Components\TextEntry::make('decision')
                                                    ->label('القرار')
                                                    ->badge()
                                                    ->color(fn ($state) => $state === 'go' ? 'success' : ($state === 'no_go' ? 'danger' : 'gray')),
                                                Infolists\Components\TextEntry::make('decision_date')
                                                    ->label('تاريخ القرار')
                                                    ->date(),
                                                Infolists\Components\TextEntry::make('decisionBy.name')
                                                    ->label('بواسطة'),
                                            ]),
                                        Infolists\Components\TextEntry::make('decision_notes')
                                            ->label('ملاحظات القرار')
                                            ->columnSpanFull(),
                                    ]),
                                Infolists\Components\Section::make('المتطلبات')
                                    ->schema([
                                        Infolists\Components\Grid::make(4)
                                            ->schema([
                                                Infolists\Components\TextEntry::make('required_classification')
                                                    ->label('التصنيف المطلوب'),
                                                Infolists\Components\TextEntry::make('minimum_experience_years')
                                                    ->label('سنوات الخبرة'),
                                                Infolists\Components\TextEntry::make('minimum_similar_projects')
                                                    ->label('المشاريع المماثلة'),
                                                Infolists\Components\TextEntry::make('minimum_project_value')
                                                    ->label('الحد الأدنى للقيمة')
                                                    ->money('JOD'),
                                            ]),
                                    ]),
                            ]),

                        // المرحلة 3: إعداد العرض
                        Tabs\Tab::make('إعداد العرض')
                            ->icon('heroicon-o-document-text')
                            ->badge(fn ($record) => $record->boqItems()->count() ?: null)
                            ->schema([
                                Infolists\Components\Section::make('ملخص التسعير')
                                    ->schema([
                                        Infolists\Components\Grid::make(4)
                                            ->schema([
                                                Infolists\Components\TextEntry::make('total_direct_cost')
                                                    ->label('التكاليف المباشرة')
                                                    ->money('JOD'),
                                                Infolists\Components\TextEntry::make('total_overhead')
                                                    ->label('المصاريف العمومية')
                                                    ->money('JOD'),
                                                Infolists\Components\TextEntry::make('markup_percentage')
                                                    ->label('نسبة الربح')
                                                    ->suffix('%'),
                                                Infolists\Components\TextEntry::make('submitted_price')
                                                    ->label('السعر المقدم')
                                                    ->money('JOD')
                                                    ->weight('bold'),
                                            ]),
                                    ]),
                                Infolists\Components\RepeatableEntry::make('boqItems')
                                    ->label('أهم بنود جدول الكميات')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('item_number')
                                            ->label('رقم'),
                                        Infolists\Components\TextEntry::make('description')
                                            ->label('الوصف'),
                                        Infolists\Components\TextEntry::make('total_price')
                                            ->label('الإجمالي')
                                            ->money('JOD'),
                                    ])
                                    ->columns(3)
                                    ->columnSpanFull(),
                            ]),

                        // المرحلة 4: التقديم
                        Tabs\Tab::make('التقديم')
                            ->icon('heroicon-o-paper-airplane')
                            ->schema([
                                Infolists\Components\Section::make('بيانات التقديم')
                                    ->schema([
                                        Infolists\Components\Grid::make(3)
                                            ->schema([
                                                Infolists\Components\TextEntry::make('submission_date')
                                                    ->label('تاريخ التقديم')
                                                    ->dateTime(),
                                                Infolists\Components\TextEntry::make('submission_method')
                                                    ->label('طريقة التقديم')
                                                    ->badge(),
                                                Infolists\Components\TextEntry::make('receipt_number')
                                                    ->label('رقم الإيصال'),
                                            ]),
                                    ]),
                                Infolists\Components\Section::make('الكفالة الابتدائية')
                                    ->schema([
                                        Infolists\Components\Grid::make(3)
                                            ->schema([
                                                Infolists\Components\TextEntry::make('bid_bond_type')
                                                    ->label('نوع الكفالة')
                                                    ->badge(),
                                                Infolists\Components\TextEntry::make('bid_bond_amount')
                                                    ->label('مبلغ الكفالة')
                                                    ->money('JOD'),
                                                Infolists\Components\TextEntry::make('bid_bond_percentage')
                                                    ->label('النسبة')
                                                    ->suffix('%'),
                                            ]),
                                    ]),
                            ]),

                        // المرحلة 5: الفتح والنتائج
                        Tabs\Tab::make('الفتح والنتائج')
                            ->icon('heroicon-o-chart-bar')
                            ->schema([
                                Infolists\Components\Section::make('نتائج الفتح')
                                    ->schema([
                                        Infolists\Components\Grid::make(3)
                                            ->schema([
                                                Infolists\Components\TextEntry::make('opening_date')
                                                    ->label('تاريخ الفتح')
                                                    ->dateTime(),
                                                Infolists\Components\TextEntry::make('result')
                                                    ->label('النتيجة')
                                                    ->badge()
                                                    ->color(fn ($state) => $state?->value === 'won' ? 'success' : ($state?->value === 'lost' ? 'danger' : 'gray')),
                                                Infolists\Components\TextEntry::make('our_rank')
                                                    ->label('ترتيبنا'),
                                            ]),
                                    ]),
                                Infolists\Components\Section::make('الفائز')
                                    ->schema([
                                        Infolists\Components\Grid::make(2)
                                            ->schema([
                                                Infolists\Components\TextEntry::make('winner_name')
                                                    ->label('اسم الفائز'),
                                                Infolists\Components\TextEntry::make('winning_price')
                                                    ->label('السعر الفائز')
                                                    ->money('JOD'),
                                            ]),
                                    ])
                                    ->visible(fn ($record) => $record->winner_name),
                            ]),

                        // المرحلة 6: الترسية والتحويل
                        Tabs\Tab::make('الترسية والتحويل')
                            ->icon('heroicon-o-trophy')
                            ->schema([
                                Infolists\Components\Section::make('قرار الإحالة')
                                    ->schema([
                                        Infolists\Components\Grid::make(2)
                                            ->schema([
                                                Infolists\Components\TextEntry::make('award_date')
                                                    ->label('تاريخ الإحالة')
                                                    ->date(),
                                                Infolists\Components\TextEntry::make('contract_id')
                                                    ->label('رقم العقد')
                                                    ->url(fn ($record) => $record->contract_id ? route('filament.admin.resources.contracts.view', $record->contract_id) : null),
                                            ]),
                                    ]),
                                Infolists\Components\Section::make('الدروس المستفادة')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('lessons_learned')
                                            ->label('')
                                            ->columnSpanFull(),
                                    ])
                                    ->visible(fn ($record) => $record->lessons_learned),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
