<?php

namespace App\Filament\Resources\TenderResource\Pages;

use App\Enums\SubmissionMethod;
use App\Enums\TenderStatus;
use App\Filament\Resources\TenderResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

/**
 * صفحة التقديم - المرحلة الرابعة
 * الصلاحية المطلوبة: tenders.submission.access
 */
class TenderSubmissionPage extends EditRecord
{
    protected static string $resource = TenderResource::class;

    protected static ?string $title = 'تقديم العطاء';

    protected static ?string $navigationLabel = 'تقديم العطاء';

    /**
     * التحقق من صلاحية الوصول
     */
    public static function canAccess(array $parameters = []): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        
        if ($user->isSuperAdmin()) return true;
        
        return $user->hasAnyPermission([
            'tenders.submission.access',
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
        
        // فقط في مرحلة Ready يمكن التقديم
        if (!in_array($this->record->status, [TenderStatus::READY, TenderStatus::PRICING])) {
            return $user->hasPermission('tenders.submission.edit_any_stage');
        }
        
        return $user->hasAnyPermission([
            'tenders.submission.edit',
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
                        Forms\Components\Placeholder::make('deadline')
                            ->label('آخر موعد للتقديم')
                            ->content(fn () => $this->record->submission_deadline?->format('Y-m-d H:i') ?? 'غير محدد'),
                        Forms\Components\Placeholder::make('days_left')
                            ->label('الأيام المتبقية')
                            ->content(function () {
                                $days = $this->record->days_until_submission;
                                if ($days === null) return 'غير محدد';
                                if ($days < 0) return '⚠️ انتهى الموعد';
                                if ($days <= 3) return "🔴 {$days} أيام";
                                if ($days <= 7) return "🟡 {$days} أيام";
                                return "🟢 {$days} يوم";
                            }),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('بيانات التقديم')
                    ->description('معلومات تقديم العطاء')
                    ->icon('heroicon-o-paper-airplane')
                    ->columns(2)
                    ->schema([
                        Forms\Components\DateTimePicker::make('submission_date')
                            ->label('تاريخ ووقت التقديم')
                            ->disabled(!$canEdit)
                            ->default(now()),
                        Forms\Components\Select::make('submission_method')
                            ->label('طريقة التقديم')
                            ->options(SubmissionMethod::class)
                            ->disabled(!$canEdit)
                            ->required(),
                        Forms\Components\TextInput::make('receipt_number')
                            ->label('رقم الإيصال/المرجع')
                            ->disabled(!$canEdit)
                            ->maxLength(100),
                        Forms\Components\TextInput::make('submitted_price')
                            ->label('السعر المقدم')
                            ->numeric()
                            ->prefix('د.أ')
                            ->disabled()
                            ->helperText('من صفحة التسعير'),
                    ]),

                Forms\Components\Section::make('الكفالة الابتدائية')
                    ->icon('heroicon-o-banknotes')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('bid_bond_type')
                            ->label('نوع الكفالة')
                            ->options([
                                'bank_guarantee' => 'كفالة بنكية',
                                'insurance' => 'بوليصة تأمين',
                                'check' => 'شيك مصدق',
                                'cash' => 'نقداً',
                            ])
                            ->disabled(!$canEdit),
                        Forms\Components\TextInput::make('bid_bond_amount')
                            ->label('مبلغ الكفالة')
                            ->numeric()
                            ->prefix('د.أ')
                            ->disabled(!$canEdit),
                    ]),

                Forms\Components\Section::make('ملاحظات التقديم')
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

            Actions\Action::make('submit_tender')
                ->label('تأكيد التقديم')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->visible(fn () => in_array($this->record->status, [TenderStatus::READY, TenderStatus::PRICING]) && $canEdit)
                ->requiresConfirmation()
                ->modalHeading('تأكيد تقديم العطاء')
                ->modalDescription('هل تم تقديم العطاء فعلاً؟ سيتم تغيير الحالة إلى "تم التقديم".')
                ->form([
                    Forms\Components\DateTimePicker::make('submission_date')
                        ->label('تاريخ ووقت التقديم')
                        ->default(now())
                        ->required(),
                    Forms\Components\Select::make('submission_method')
                        ->label('طريقة التقديم')
                        ->options(SubmissionMethod::class)
                        ->required(),
                    Forms\Components\TextInput::make('receipt_number')
                        ->label('رقم الإيصال')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'status' => TenderStatus::SUBMITTED,
                        'submission_date' => $data['submission_date'],
                        'submission_method' => $data['submission_method'],
                        'receipt_number' => $data['receipt_number'],
                        'submitted_by' => auth()->id(),
                    ]);
                    
                    Notification::make()
                        ->title('✅ تم تسجيل تقديم العطاء')
                        ->success()
                        ->send();
                    
                    $this->redirect(TenderResource::getUrl('view', ['record' => $this->record]));
                }),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم حفظ بيانات التقديم';
    }
}
