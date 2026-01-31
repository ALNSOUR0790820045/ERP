<?php

namespace App\Filament\Resources\TenderResource\Pages;

use App\Enums\TenderResult;
use App\Enums\TenderStatus;
use App\Filament\Resources\TenderResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

/**
 * صفحة الترسية والنتيجة النهائية - المرحلة السادسة
 * الصلاحية المطلوبة: tenders.award.access
 */
class TenderAwardPage extends EditRecord
{
    protected static string $resource = TenderResource::class;

    protected static ?string $title = 'الترسية والنتيجة';

    protected static ?string $navigationLabel = 'الترسية والنتيجة';

    /**
     * التحقق من صلاحية الوصول
     */
    public static function canAccess(array $parameters = []): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        
        if ($user->isSuperAdmin()) return true;
        
        return $user->hasAnyPermission([
            'tenders.award.access',
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
        
        // بعد الفتح
        if (!in_array($this->record->status, [TenderStatus::OPENING, TenderStatus::WON, TenderStatus::LOST])) {
            return $user->hasPermission('tenders.award.edit_any_stage');
        }
        
        return $user->hasAnyPermission([
            'tenders.award.edit',
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
                        Forms\Components\Placeholder::make('our_rank')
                            ->label('ترتيبنا في الفتح')
                            ->content(fn () => $this->record->our_rank ? '#' . $this->record->our_rank : 'غير مسجل'),
                        Forms\Components\Placeholder::make('our_price')
                            ->label('سعرنا المقدم')
                            ->content(fn () => number_format($this->record->submitted_price ?? 0, 2) . ' د.أ'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('النتيجة النهائية')
                    ->description('تسجيل نتيجة الترسية')
                    ->icon('heroicon-o-trophy')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('result')
                            ->label('النتيجة')
                            ->options(TenderResult::class)
                            ->disabled(!$canEdit)
                            ->live(),
                        Forms\Components\DatePicker::make('award_date')
                            ->label('تاريخ الترسية')
                            ->disabled(!$canEdit),
                    ]),

                Forms\Components\Section::make('تفاصيل الفوز')
                    ->icon('heroicon-o-star')
                    ->visible(fn (Forms\Get $get) => $get('result') === 'won' || $this->record->result?->value === 'won')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('winning_price')
                            ->label('قيمة العقد')
                            ->numeric()
                            ->prefix('د.أ')
                            ->disabled(!$canEdit),
                        Forms\Components\Placeholder::make('price_diff')
                            ->label('فرق السعر')
                            ->content(function () {
                                $submitted = $this->record->submitted_price ?? 0;
                                $winning = $this->record->winning_price ?? $submitted;
                                if ($winning == 0) return '-';
                                $diff = $winning - $submitted;
                                return ($diff >= 0 ? '+' : '') . number_format($diff, 2) . ' د.أ';
                            }),
                    ]),

                Forms\Components\Section::make('تفاصيل الخسارة')
                    ->icon('heroicon-o-x-circle')
                    ->visible(fn (Forms\Get $get) => $get('result') === 'lost' || $this->record->result?->value === 'lost')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('winner_name')
                            ->label('اسم الفائز')
                            ->disabled(!$canEdit)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('winning_price')
                            ->label('السعر الفائز')
                            ->numeric()
                            ->prefix('د.أ')
                            ->disabled(!$canEdit),
                        Forms\Components\Textarea::make('loss_reason')
                            ->label('سبب الخسارة')
                            ->rows(3)
                            ->disabled(!$canEdit)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('الدروس المستفادة')
                    ->icon('heroicon-o-light-bulb')
                    ->schema([
                        Forms\Components\Textarea::make('lessons_learned')
                            ->label('الدروس المستفادة')
                            ->rows(4)
                            ->disabled(!$canEdit)
                            ->helperText('سجل الدروس المستفادة لتحسين الأداء مستقبلاً')
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

            Actions\Action::make('record_win')
                ->label('🏆 تسجيل فوز')
                ->icon('heroicon-o-trophy')
                ->color('success')
                ->visible(fn () => $this->record->status === TenderStatus::OPENING && $canEdit)
                ->form([
                    Forms\Components\DatePicker::make('award_date')
                        ->label('تاريخ الترسية')
                        ->default(now())
                        ->required(),
                    Forms\Components\TextInput::make('winning_price')
                        ->label('قيمة العقد')
                        ->numeric()
                        ->prefix('د.أ')
                        ->default(fn () => $this->record->submitted_price)
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'status' => TenderStatus::WON,
                        'result' => TenderResult::WON,
                        'award_date' => $data['award_date'],
                        'winning_price' => $data['winning_price'],
                    ]);
                    
                    Notification::make()
                        ->title('🎉 مبروك! تم تسجيل الفوز بالعطاء')
                        ->success()
                        ->send();
                    
                    $this->redirect(TenderResource::getUrl('view', ['record' => $this->record]));
                }),

            Actions\Action::make('record_loss')
                ->label('تسجيل خسارة')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $this->record->status === TenderStatus::OPENING && $canEdit)
                ->form([
                    Forms\Components\DatePicker::make('award_date')
                        ->label('تاريخ الإعلان')
                        ->default(now())
                        ->required(),
                    Forms\Components\TextInput::make('winner_name')
                        ->label('اسم الفائز')
                        ->required(),
                    Forms\Components\TextInput::make('winning_price')
                        ->label('السعر الفائز')
                        ->numeric()
                        ->prefix('د.أ'),
                    Forms\Components\Textarea::make('loss_reason')
                        ->label('سبب الخسارة')
                        ->rows(2),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'status' => TenderStatus::LOST,
                        'result' => TenderResult::LOST,
                        'award_date' => $data['award_date'],
                        'winner_name' => $data['winner_name'],
                        'winning_price' => $data['winning_price'],
                        'loss_reason' => $data['loss_reason'],
                    ]);
                    
                    Notification::make()
                        ->title('تم تسجيل خسارة العطاء')
                        ->warning()
                        ->send();
                    
                    $this->redirect(TenderResource::getUrl('view', ['record' => $this->record]));
                }),

            Actions\Action::make('record_cancelled')
                ->label('إلغاء العطاء')
                ->icon('heroicon-o-no-symbol')
                ->color('gray')
                ->visible(fn () => in_array($this->record->status, [TenderStatus::OPENING, TenderStatus::SUBMITTED]) && $canEdit)
                ->requiresConfirmation()
                ->modalHeading('تسجيل إلغاء العطاء')
                ->form([
                    Forms\Components\Textarea::make('loss_reason')
                        ->label('سبب الإلغاء')
                        ->required()
                        ->rows(2),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'status' => TenderStatus::CANCELLED,
                        'result' => TenderResult::CANCELLED,
                        'loss_reason' => $data['loss_reason'],
                    ]);
                    
                    Notification::make()
                        ->title('تم تسجيل إلغاء العطاء')
                        ->info()
                        ->send();
                    
                    $this->redirect(TenderResource::getUrl('view', ['record' => $this->record]));
                }),

            Actions\Action::make('convert_to_project')
                ->label('تحويل لمشروع')
                ->icon('heroicon-o-building-office-2')
                ->color('success')
                ->visible(fn () => $this->record->status === TenderStatus::WON && !$this->record->contract_id)
                ->url(fn () => route('filament.admin.resources.projects.create', ['tender_id' => $this->record->id])),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم حفظ بيانات الترسية';
    }
}
