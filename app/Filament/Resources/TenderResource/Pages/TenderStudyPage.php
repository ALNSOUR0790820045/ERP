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
 * صفحة الدراسة والقرار - المرحلة الثانية
 * الصلاحية المطلوبة: tenders.study.access
 */
class TenderStudyPage extends EditRecord
{
    protected static string $resource = TenderResource::class;

    protected static ?string $title = 'الدراسة والقرار';

    protected static ?string $navigationLabel = 'الدراسة والقرار';

    /**
     * التحقق من صلاحية الوصول
     */
    public static function canAccess(array $parameters = []): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        
        if ($user->isSuperAdmin()) return true;
        
        return $user->hasAnyPermission([
            'tenders.study.access',
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
        
        // فقط في مرحلة الدراسة يمكن التعديل
        if (!in_array($this->record->status, [TenderStatus::STUDYING, TenderStatus::NEW])) {
            return $user->hasPermission('tenders.study.edit_any_stage');
        }
        
        return $user->hasAnyPermission([
            'tenders.study.edit',
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
                    ])
                    ->columnSpanFull(),

                Forms\Components\Section::make('متطلبات التأهيل')
                    ->description('المتطلبات الفنية والمالية للتأهل')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('required_classification')
                            ->label('التصنيف المطلوب')
                            ->disabled(!$canEdit)
                            ->placeholder('مثال: أولى إنشاءات'),
                        Forms\Components\TextInput::make('minimum_experience_years')
                            ->label('سنوات الخبرة المطلوبة')
                            ->numeric()
                            ->disabled(!$canEdit)
                            ->suffix('سنة'),
                        Forms\Components\TextInput::make('minimum_similar_projects')
                            ->label('المشاريع المماثلة المطلوبة')
                            ->numeric()
                            ->disabled(!$canEdit)
                            ->suffix('مشروع'),
                        Forms\Components\TextInput::make('minimum_project_value')
                            ->label('الحد الأدنى لقيمة المشروع')
                            ->numeric()
                            ->prefix('د.أ')
                            ->disabled(!$canEdit),
                    ]),

                Forms\Components\Section::make('الكفالات المطلوبة')
                    ->icon('heroicon-o-banknotes')
                    ->columns(4)
                    ->schema([
                        Forms\Components\TextInput::make('bid_bond_percentage')
                            ->label('كفالة العطاء %')
                            ->numeric()
                            ->suffix('%')
                            ->disabled(!$canEdit)
                            ->default(1),
                        Forms\Components\TextInput::make('performance_bond_percentage')
                            ->label('كفالة الأداء %')
                            ->numeric()
                            ->suffix('%')
                            ->disabled(!$canEdit)
                            ->default(10),
                        Forms\Components\TextInput::make('advance_payment_percentage')
                            ->label('الدفعة المقدمة %')
                            ->numeric()
                            ->suffix('%')
                            ->disabled(!$canEdit)
                            ->default(10),
                        Forms\Components\TextInput::make('retention_percentage')
                            ->label('المحتجزات %')
                            ->numeric()
                            ->suffix('%')
                            ->disabled(!$canEdit)
                            ->default(10),
                    ]),

                Forms\Components\Section::make('تفاصيل المتطلبات')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Forms\Components\Textarea::make('technical_requirements')
                            ->label('المتطلبات الفنية')
                            ->rows(4)
                            ->disabled(!$canEdit)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('financial_requirements')
                            ->label('المتطلبات المالية')
                            ->rows(3)
                            ->disabled(!$canEdit)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('other_requirements')
                            ->label('متطلبات أخرى')
                            ->rows(3)
                            ->disabled(!$canEdit)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('ملاحظات الدراسة')
                    ->icon('heroicon-o-pencil-square')
                    ->schema([
                        Forms\Components\Textarea::make('decision_notes')
                            ->label('ملاحظات القرار')
                            ->rows(4)
                            ->disabled(!$canEdit)
                            ->helperText('سجل ملاحظاتك وتحليلك للعطاء هنا')
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

            Actions\Action::make('go_decision')
                ->label('✅ Go - المشاركة')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => in_array($this->record->status, [TenderStatus::STUDYING, TenderStatus::NEW]) && $canEdit)
                ->requiresConfirmation()
                ->modalHeading('قرار المشاركة (Go)')
                ->modalDescription('هل أنت متأكد من قرار المشاركة في هذا العطاء؟')
                ->form([
                    Forms\Components\Textarea::make('decision_notes')
                        ->label('مبررات القرار')
                        ->required()
                        ->rows(3),
                    Forms\Components\Select::make('priority')
                        ->label('الأولوية')
                        ->options([
                            'high' => 'عالية',
                            'medium' => 'متوسطة',
                            'low' => 'منخفضة',
                        ])
                        ->default('medium'),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'status' => TenderStatus::GO,
                        'decision' => 'go',
                        'decision_date' => now(),
                        'decision_by' => auth()->id(),
                        'decision_notes' => $data['decision_notes'],
                        'priority' => $data['priority'] ?? 'medium',
                    ]);
                    
                    Notification::make()
                        ->title('✅ تم اعتماد قرار المشاركة')
                        ->success()
                        ->send();
                    
                    $this->redirect(TenderResource::getUrl('view', ['record' => $this->record]));
                }),

            Actions\Action::make('no_go_decision')
                ->label('❌ No-Go - عدم المشاركة')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => in_array($this->record->status, [TenderStatus::STUDYING, TenderStatus::NEW]) && $canEdit)
                ->requiresConfirmation()
                ->modalHeading('قرار عدم المشاركة (No-Go)')
                ->modalDescription('هل أنت متأكد من قرار عدم المشاركة؟')
                ->form([
                    Forms\Components\Textarea::make('decision_notes')
                        ->label('أسباب عدم المشاركة')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'status' => TenderStatus::NO_GO,
                        'decision' => 'no_go',
                        'decision_date' => now(),
                        'decision_by' => auth()->id(),
                        'decision_notes' => $data['decision_notes'],
                    ]);
                    
                    Notification::make()
                        ->title('تم تسجيل قرار عدم المشاركة')
                        ->warning()
                        ->send();
                    
                    $this->redirect(TenderResource::getUrl('view', ['record' => $this->record]));
                }),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم حفظ بيانات الدراسة';
    }
}
