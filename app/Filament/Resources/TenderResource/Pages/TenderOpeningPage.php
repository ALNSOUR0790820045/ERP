<?php

namespace App\Filament\Resources\TenderResource\Pages;

use App\Enums\TenderStatus;
use App\Filament\Resources\TenderResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

/**
 * صفحة الفتح والنتائج - المرحلة الخامسة
 * الصلاحية المطلوبة: tenders.opening.access
 */
class TenderOpeningPage extends EditRecord
{
    protected static string $resource = TenderResource::class;

    protected static ?string $title = 'الفتح والنتائج';

    protected static ?string $navigationLabel = 'الفتح والنتائج';

    /**
     * التحقق من صلاحية الوصول
     */
    public static function canAccess(array $parameters = []): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        
        if ($user->isSuperAdmin()) return true;
        
        return $user->hasAnyPermission([
            'tenders.opening.access',
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
        
        // فقط بعد التقديم
        if (!in_array($this->record->status, [TenderStatus::SUBMITTED, TenderStatus::OPENING])) {
            return $user->hasPermission('tenders.opening.edit_any_stage');
        }
        
        return $user->hasAnyPermission([
            'tenders.opening.edit',
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
                        Forms\Components\Placeholder::make('our_price')
                            ->label('سعرنا المقدم')
                            ->content(fn () => number_format($this->record->submitted_price ?? 0, 2) . ' د.أ'),
                        Forms\Components\Placeholder::make('opening_date_scheduled')
                            ->label('موعد الفتح المجدول')
                            ->content(fn () => $this->record->opening_date?->format('Y-m-d H:i') ?? 'غير محدد'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('نتائج فتح المظاريف')
                    ->description('تسجيل نتائج جلسة الفتح')
                    ->icon('heroicon-o-envelope-open')
                    ->columns(2)
                    ->schema([
                        Forms\Components\DateTimePicker::make('opening_date')
                            ->label('تاريخ ووقت الفتح الفعلي')
                            ->disabled(!$canEdit),
                        Forms\Components\TextInput::make('our_rank')
                            ->label('ترتيبنا')
                            ->numeric()
                            ->disabled(!$canEdit)
                            ->helperText('رقم الترتيب بين المتنافسين'),
                    ]),

                Forms\Components\Section::make('معلومات المنافسين')
                    ->description('لإضافة تفاصيل المنافسين، استخدم علاقة المنافسين في صفحة العرض')
                    ->icon('heroicon-o-users')
                    ->collapsed()
                    ->schema([
                        Forms\Components\Placeholder::make('competitors_hint')
                            ->label('')
                            ->content('يمكنك إضافة تفاصيل كل منافس من خلال علاقة "المنافسين" في صفحة العرض الرئيسية.'),
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

            Actions\Action::make('manage_competitors')
                ->label('إدارة المنافسين')
                ->icon('heroicon-o-users')
                ->color('info')
                ->url(fn () => TenderResource::getUrl('view', ['record' => $this->record]) . '?activeRelationManager=5'),

            Actions\Action::make('record_opening')
                ->label('تسجيل نتائج الفتح')
                ->icon('heroicon-o-envelope-open')
                ->color('primary')
                ->visible(fn () => $this->record->status === TenderStatus::SUBMITTED && $canEdit)
                ->form([
                    Forms\Components\DateTimePicker::make('opening_date')
                        ->label('تاريخ الفتح')
                        ->default(now())
                        ->required(),
                    Forms\Components\TextInput::make('our_rank')
                        ->label('ترتيبنا')
                        ->numeric()
                        ->required()
                        ->helperText('1 = الأول'),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'status' => TenderStatus::OPENING,
                        'opening_date' => $data['opening_date'],
                        'our_rank' => $data['our_rank'],
                    ]);
                    
                    $message = $data['our_rank'] == 1 
                        ? '🎉 أنتم الأول! انتظار الترسية'
                        : 'تم تسجيل النتائج - الترتيب: #' . $data['our_rank'];
                    
                    Notification::make()
                        ->title($message)
                        ->success()
                        ->send();
                    
                    $this->redirect(TenderResource::getUrl('view', ['record' => $this->record]));
                }),

            Actions\Action::make('proceed_to_award')
                ->label('الانتقال للترسية')
                ->icon('heroicon-o-trophy')
                ->color('success')
                ->visible(fn () => $this->record->status === TenderStatus::OPENING && $canEdit)
                ->url(fn () => TenderResource::getUrl('award', ['record' => $this->record])),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم حفظ نتائج الفتح';
    }
}
