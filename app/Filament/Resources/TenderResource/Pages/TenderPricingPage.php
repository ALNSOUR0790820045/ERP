<?php

namespace App\Filament\Resources\TenderResource\Pages;

use App\Enums\TenderStatus;
use App\Filament\Resources\TenderResource;
use App\Models\Tender;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

/**
 * صفحة إعداد العرض والتسعير - المرحلة الثالثة
 * الصلاحية المطلوبة: tenders.pricing.access
 */
class TenderPricingPage extends EditRecord
{
    protected static string $resource = TenderResource::class;

    protected static ?string $title = 'إعداد العرض والتسعير';

    protected static ?string $navigationLabel = 'إعداد العرض والتسعير';

    /**
     * التحقق من صلاحية الوصول
     */
    public static function canAccess(array $parameters = []): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        
        if ($user->isSuperAdmin()) return true;
        
        return $user->hasAnyPermission([
            'tenders.pricing.access',
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
        
        // فقط في مرحلة Go أو التسعير يمكن التعديل
        if (!in_array($this->record->status, [TenderStatus::GO, TenderStatus::PRICING, TenderStatus::READY])) {
            return $user->hasPermission('tenders.pricing.edit_any_stage');
        }
        
        return $user->hasAnyPermission([
            'tenders.pricing.edit',
            'tenders.tender.update',
        ]);
    }

    public function form(Form $form): Form
    {
        $canEdit = $this->canEdit();
        
        return $form
            ->schema([
                // شريط الحالة
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Placeholder::make('current_status')
                            ->label('الحالة الحالية')
                            ->content(fn () => $this->record->status->getLabel()),
                        Forms\Components\Placeholder::make('boq_count')
                            ->label('عدد بنود BOQ')
                            ->content(fn () => $this->record->boqItems()->count() . ' بند'),
                        Forms\Components\Placeholder::make('total_boq')
                            ->label('إجمالي BOQ')
                            ->content(fn () => number_format($this->record->boqItems()->sum('total_price'), 2) . ' د.أ'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('التكاليف المباشرة')
                    ->description('ملخص التكاليف من جدول الكميات')
                    ->icon('heroicon-o-calculator')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('total_direct_cost')
                            ->label('إجمالي التكاليف المباشرة')
                            ->numeric()
                            ->prefix('د.أ')
                            ->disabled(!$canEdit)
                            ->helperText('يُحسب من بنود BOQ'),
                        Forms\Components\TextInput::make('total_overhead')
                            ->label('المصاريف العمومية')
                            ->numeric()
                            ->prefix('د.أ')
                            ->disabled(!$canEdit),
                        Forms\Components\TextInput::make('total_cost')
                            ->label('إجمالي التكلفة')
                            ->numeric()
                            ->prefix('د.أ')
                            ->disabled()
                            ->helperText('التكاليف المباشرة + المصاريف'),
                    ]),

                Forms\Components\Section::make('التسعير والربح')
                    ->icon('heroicon-o-currency-dollar')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('markup_percentage')
                            ->label('نسبة الربح (Markup)')
                            ->numeric()
                            ->suffix('%')
                            ->disabled(!$canEdit)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                $totalCost = $get('total_cost') ?? 0;
                                if ($totalCost > 0 && $state) {
                                    $markup = $totalCost * ($state / 100);
                                    $set('markup_amount', $markup);
                                    $set('submitted_price', $totalCost + $markup);
                                }
                            }),
                        Forms\Components\TextInput::make('markup_amount')
                            ->label('مبلغ الربح')
                            ->numeric()
                            ->prefix('د.أ')
                            ->disabled(),
                        Forms\Components\TextInput::make('submitted_price')
                            ->label('السعر المقدم')
                            ->numeric()
                            ->prefix('د.أ')
                            ->disabled(!$canEdit)
                            ->required()
                            ->helperText('السعر النهائي للعرض'),
                    ]),

                Forms\Components\Section::make('مقارنة الأسعار')
                    ->icon('heroicon-o-chart-bar')
                    ->columns(3)
                    ->collapsed()
                    ->schema([
                        Forms\Components\Placeholder::make('estimated_vs_submitted')
                            ->label('مقارنة مع التقديري')
                            ->content(function () {
                                $estimated = $this->record->estimated_value ?? 0;
                                $submitted = $this->record->submitted_price ?? 0;
                                if ($estimated == 0) return 'لا توجد قيمة تقديرية';
                                $diff = (($submitted - $estimated) / $estimated) * 100;
                                $sign = $diff >= 0 ? '+' : '';
                                return $sign . number_format($diff, 1) . '%';
                            }),
                        Forms\Components\Placeholder::make('profit_margin')
                            ->label('هامش الربح')
                            ->content(function () {
                                $cost = $this->record->total_cost ?? 0;
                                $price = $this->record->submitted_price ?? 0;
                                if ($price == 0) return '-';
                                $margin = (($price - $cost) / $price) * 100;
                                return number_format($margin, 1) . '%';
                            }),
                        Forms\Components\Placeholder::make('estimated_value')
                            ->label('القيمة التقديرية')
                            ->content(fn () => number_format($this->record->estimated_value ?? 0, 2) . ' د.أ'),
                    ]),

                Forms\Components\Section::make('ملاحظات التسعير')
                    ->icon('heroicon-o-pencil-square')
                    ->schema([
                        Forms\Components\Textarea::make('additional_notes')
                            ->label('ملاحظات إضافية')
                            ->rows(3)
                            ->disabled(!$canEdit)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        $canEdit = $this->canEdit();
        
        return [
            Actions\Action::make('back_to_view')
                ->label('العودة للعرض')
                ->icon('heroicon-o-arrow-right')
                ->color('gray')
                ->url(fn () => TenderResource::getUrl('view', ['record' => $this->record])),

            Actions\Action::make('manage_boq')
                ->label('إدارة جدول الكميات')
                ->icon('heroicon-o-table-cells')
                ->color('info')
                ->url(fn () => TenderResource::getUrl('view', ['record' => $this->record]) . '?activeRelationManager=3'),

            Actions\Action::make('start_pricing')
                ->label('بدء التسعير')
                ->icon('heroicon-o-play')
                ->color('primary')
                ->visible(fn () => $this->record->status === TenderStatus::GO && $canEdit)
                ->action(function () {
                    $this->record->update(['status' => TenderStatus::PRICING]);
                    
                    Notification::make()
                        ->title('تم بدء مرحلة التسعير')
                        ->success()
                        ->send();
                    
                    $this->redirect(TenderResource::getUrl('pricing', ['record' => $this->record]));
                }),

            Actions\Action::make('mark_ready')
                ->label('جاهز للتقديم')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn () => $this->record->status === TenderStatus::PRICING && $canEdit)
                ->requiresConfirmation()
                ->modalHeading('تأكيد جاهزية العرض')
                ->form([
                    Forms\Components\CheckboxList::make('checklist')
                        ->label('قائمة التحقق')
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
                    
                    Notification::make()
                        ->title('✅ العرض جاهز للتقديم')
                        ->success()
                        ->send();
                    
                    $this->redirect(TenderResource::getUrl('view', ['record' => $this->record]));
                }),

            Actions\Action::make('calculate_costs')
                ->label('حساب التكاليف')
                ->icon('heroicon-o-calculator')
                ->color('warning')
                ->visible(fn () => $canEdit)
                ->action(function () {
                    $this->record->calculateTotalCost();
                    
                    Notification::make()
                        ->title('تم تحديث التكاليف')
                        ->success()
                        ->send();
                    
                    $this->redirect(TenderResource::getUrl('pricing', ['record' => $this->record]));
                }),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم حفظ بيانات التسعير';
    }
}
