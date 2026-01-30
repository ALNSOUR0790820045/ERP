<?php

namespace App\Filament\Resources\TenderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;
use Carbon\Carbon;

class ClarificationsRelationManager extends RelationManager
{
    protected static string $relationship = 'clarifications';

    protected static ?string $title = 'الاستفسارات والتوضيحات';
    
    protected static ?string $modelLabel = 'استفسار';
    
    protected static ?string $pluralModelLabel = 'الاستفسارات';
    
    protected static ?string $icon = 'heroicon-o-chat-bubble-left-right';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الاستفسار')
                    ->icon('heroicon-o-question-mark-circle')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('question_number')
                            ->label('رقم الاستفسار')
                            ->maxLength(50)
                            ->placeholder('مثال: Q-001'),
                            
                        Forms\Components\DatePicker::make('question_date')
                            ->label('تاريخ الاستفسار')
                            ->default(now())
                            ->native(false)
                            ->required(),
                            
                        Forms\Components\Select::make('priority')
                            ->label('الأولوية')
                            ->options([
                                'low' => 'منخفضة',
                                'medium' => 'متوسطة',
                                'high' => 'عالية',
                                'critical' => 'حرجة',
                            ])
                            ->default('medium')
                            ->native(false),
                    ]),
                    
                Forms\Components\Section::make('السؤال')
                    ->icon('heroicon-o-chat-bubble-bottom-center-text')
                    ->schema([
                        Forms\Components\Select::make('question_type')
                            ->label('نوع السؤال')
                            ->options([
                                'technical' => 'فني',
                                'commercial' => 'تجاري',
                                'contractual' => 'تعاقدي',
                                'clarification' => 'توضيحي',
                                'other' => 'أخرى',
                            ])
                            ->default('technical')
                            ->native(false)
                            ->columnSpan(1),
                            
                        Forms\Components\TextInput::make('reference')
                            ->label('المرجع')
                            ->placeholder('مثال: الصفحة 25، البند 3.2.1')
                            ->maxLength(255)
                            ->columnSpan(1),
                            
                        Forms\Components\Textarea::make('question')
                            ->label('نص السؤال')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('اكتب السؤال بوضوح...'),
                            
                        Forms\Components\FileUpload::make('question_attachments')
                            ->label('مرفقات السؤال')
                            ->multiple()
                            ->directory('clarification-questions')
                            ->columnSpanFull(),
                    ])->columns(2),
                    
                Forms\Components\Section::make('الإجابة')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options([
                                'draft' => 'مسودة',
                                'submitted' => 'تم الإرسال',
                                'pending' => 'قيد الانتظار',
                                'answered' => 'تمت الإجابة',
                                'no_response' => 'بدون رد',
                                'cancelled' => 'ملغي',
                            ])
                            ->default('draft')
                            ->native(false)
                            ->live(),
                            
                        Forms\Components\DatePicker::make('submitted_date')
                            ->label('تاريخ الإرسال')
                            ->native(false),
                            
                        Forms\Components\DatePicker::make('answer_date')
                            ->label('تاريخ الجواب')
                            ->native(false),
                            
                        Forms\Components\Textarea::make('answer')
                            ->label('نص الإجابة')
                            ->rows(3)
                            ->columnSpanFull(),
                            
                        Forms\Components\FileUpload::make('answer_attachments')
                            ->label('مرفقات الإجابة')
                            ->multiple()
                            ->directory('clarification-answers')
                            ->columnSpanFull(),
                    ])->columns(3),
                    
                Forms\Components\Section::make('التأثير والملاحظات')
                    ->collapsed()
                    ->schema([
                        Forms\Components\Select::make('impact')
                            ->label('تأثير الإجابة')
                            ->options([
                                'none' => 'لا يوجد',
                                'minor' => 'بسيط',
                                'moderate' => 'متوسط',
                                'major' => 'كبير',
                            ])
                            ->native(false),
                            
                        Forms\Components\Textarea::make('impact_notes')
                            ->label('ملاحظات التأثير')
                            ->rows(2)
                            ->columnSpan(2),
                            
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات عامة')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(3),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('question_number')
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('question_number')
                    ->label('الرقم')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                    
                Tables\Columns\TextColumn::make('question_type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'technical' => 'فني',
                        'commercial' => 'تجاري',
                        'contractual' => 'تعاقدي',
                        'clarification' => 'توضيحي',
                        default => 'أخرى',
                    })
                    ->color(fn ($state) => match($state) {
                        'technical' => 'info',
                        'commercial' => 'success',
                        'contractual' => 'warning',
                        'clarification' => 'gray',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('question')
                    ->label('السؤال')
                    ->limit(50)
                    ->wrap()
                    ->searchable()
                    ->tooltip(fn ($state) => $state),
                    
                Tables\Columns\TextColumn::make('question_date')
                    ->label('التاريخ')
                    ->date('Y-m-d')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('priority')
                    ->label('الأولوية')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'low' => 'منخفضة',
                        'medium' => 'متوسطة',
                        'high' => 'عالية',
                        'critical' => 'حرجة',
                        default => $state,
                    })
                    ->color(fn ($state) => match($state) {
                        'low' => 'gray',
                        'medium' => 'info',
                        'high' => 'warning',
                        'critical' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn ($state) => match($state) {
                        'critical' => 'heroicon-o-exclamation-triangle',
                        'high' => 'heroicon-o-arrow-up',
                        default => null,
                    }),
                    
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'draft' => 'مسودة',
                        'submitted' => 'تم الإرسال',
                        'pending' => 'قيد الانتظار',
                        'answered' => 'تمت الإجابة',
                        'no_response' => 'بدون رد',
                        'cancelled' => 'ملغي',
                        default => $state,
                    })
                    ->color(fn ($state) => match($state) {
                        'draft' => 'gray',
                        'submitted' => 'info',
                        'pending' => 'warning',
                        'answered' => 'success',
                        'no_response' => 'danger',
                        'cancelled' => 'gray',
                        default => 'gray',
                    })
                    ->icon(fn ($state) => match($state) {
                        'answered' => 'heroicon-o-check-circle',
                        'pending' => 'heroicon-o-clock',
                        'no_response' => 'heroicon-o-x-circle',
                        default => null,
                    }),
                    
                Tables\Columns\TextColumn::make('answer_date')
                    ->label('تاريخ الرد')
                    ->date('Y-m-d')
                    ->placeholder('لم يتم الرد')
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('days_waiting')
                    ->label('أيام الانتظار')
                    ->getStateUsing(fn ($record) => 
                        $record->status === 'answered' || $record->status === 'no_response' || $record->status === 'cancelled'
                            ? '-'
                            : Carbon::parse($record->question_date)->diffInDays(now())
                    )
                    ->badge()
                    ->color(fn ($state) => 
                        $state === '-' ? 'gray' : ($state > 7 ? 'danger' : ($state > 3 ? 'warning' : 'success'))
                    )
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'draft' => 'مسودة',
                        'submitted' => 'تم الإرسال',
                        'pending' => 'قيد الانتظار',
                        'answered' => 'تمت الإجابة',
                        'no_response' => 'بدون رد',
                        'cancelled' => 'ملغي',
                    ]),
                    
                Tables\Filters\SelectFilter::make('question_type')
                    ->label('نوع السؤال')
                    ->options([
                        'technical' => 'فني',
                        'commercial' => 'تجاري',
                        'contractual' => 'تعاقدي',
                        'clarification' => 'توضيحي',
                        'other' => 'أخرى',
                    ]),
                    
                Tables\Filters\SelectFilter::make('priority')
                    ->label('الأولوية')
                    ->options([
                        'low' => 'منخفضة',
                        'medium' => 'متوسطة',
                        'high' => 'عالية',
                        'critical' => 'حرجة',
                    ]),
                    
                Tables\Filters\Filter::make('unanswered')
                    ->label('بدون إجابة')
                    ->query(fn ($query) => $query->whereIn('status', ['submitted', 'pending']))
                    ->toggle(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->icon('heroicon-o-plus')
                    ->label('إضافة استفسار'),
                    
                Tables\Actions\Action::make('quick_question')
                    ->label('سؤال سريع')
                    ->icon('heroicon-o-bolt')
                    ->color('warning')
                    ->form([
                        Forms\Components\Textarea::make('question')
                            ->label('السؤال')
                            ->required()
                            ->rows(3),
                        Forms\Components\Select::make('question_type')
                            ->label('النوع')
                            ->options([
                                'technical' => 'فني',
                                'commercial' => 'تجاري',
                                'clarification' => 'توضيحي',
                            ])
                            ->default('technical'),
                    ])
                    ->action(function (array $data) {
                        $tender = $this->getOwnerRecord();
                        $count = $tender->clarifications()->count() + 1;
                        
                        $tender->clarifications()->create([
                            'question_number' => 'Q-' . str_pad($count, 3, '0', STR_PAD_LEFT),
                            'question' => $data['question'],
                            'question_type' => $data['question_type'],
                            'question_date' => now(),
                            'status' => 'draft',
                            'priority' => 'medium',
                        ]);
                        
                        Notification::make()
                            ->title('تمت إضافة الاستفسار')
                            ->success()
                            ->send();
                    }),
                    
                Tables\Actions\Action::make('summary')
                    ->label('ملخص')
                    ->icon('heroicon-o-chart-pie')
                    ->color('info')
                    ->modalHeading('ملخص الاستفسارات')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('إغلاق')
                    ->modalContent(function () {
                        $tender = $this->getOwnerRecord();
                        $clarifications = $tender->clarifications;
                        
                        $total = $clarifications->count();
                        $answered = $clarifications->where('status', 'answered')->count();
                        $pending = $clarifications->whereIn('status', ['submitted', 'pending'])->count();
                        
                        return new HtmlString("
                            <div class='grid grid-cols-3 gap-4 text-center'>
                                <div class='bg-primary-50 dark:bg-primary-900/20 p-4 rounded-lg'>
                                    <div class='text-2xl font-bold text-primary-600'>{$total}</div>
                                    <div class='text-sm text-gray-600'>إجمالي</div>
                                </div>
                                <div class='bg-success-50 dark:bg-success-900/20 p-4 rounded-lg'>
                                    <div class='text-2xl font-bold text-success-600'>{$answered}</div>
                                    <div class='text-sm text-gray-600'>تمت الإجابة</div>
                                </div>
                                <div class='bg-warning-50 dark:bg-warning-900/20 p-4 rounded-lg'>
                                    <div class='text-2xl font-bold text-warning-600'>{$pending}</div>
                                    <div class='text-sm text-gray-600'>قيد الانتظار</div>
                                </div>
                            </div>
                        ");
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    
                    Tables\Actions\Action::make('submit')
                        ->label('إرسال')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('info')
                        ->visible(fn ($record) => $record->status === 'draft')
                        ->action(function ($record) {
                            $record->update([
                                'status' => 'submitted',
                                'submitted_date' => now(),
                            ]);
                            Notification::make()->title('تم إرسال الاستفسار')->success()->send();
                        }),
                        
                    Tables\Actions\Action::make('record_answer')
                        ->label('تسجيل الإجابة')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->visible(fn ($record) => in_array($record->status, ['submitted', 'pending']))
                        ->form([
                            Forms\Components\Textarea::make('answer')
                                ->label('الإجابة')
                                ->required()
                                ->rows(3),
                            Forms\Components\DatePicker::make('answer_date')
                                ->label('تاريخ الإجابة')
                                ->default(now())
                                ->required(),
                            Forms\Components\Select::make('impact')
                                ->label('التأثير')
                                ->options([
                                    'none' => 'لا يوجد',
                                    'minor' => 'بسيط',
                                    'moderate' => 'متوسط',
                                    'major' => 'كبير',
                                ]),
                        ])
                        ->action(function ($record, array $data) {
                            $record->update([
                                'answer' => $data['answer'],
                                'answer_date' => $data['answer_date'],
                                'impact' => $data['impact'] ?? null,
                                'status' => 'answered',
                            ]);
                            Notification::make()->title('تم تسجيل الإجابة')->success()->send();
                        }),
                        
                    Tables\Actions\Action::make('no_response')
                        ->label('لا رد')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->visible(fn ($record) => in_array($record->status, ['submitted', 'pending']))
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->update(['status' => 'no_response']);
                            Notification::make()->title('تم التحديث')->info()->send();
                        }),
                        
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('mark_submitted')
                        ->label('إرسال المحدد')
                        ->icon('heroicon-o-paper-airplane')
                        ->action(function ($records) {
                            $records->each->update([
                                'status' => 'submitted',
                                'submitted_date' => now(),
                            ]);
                            Notification::make()->title('تم الإرسال')->success()->send();
                        }),
                ]),
            ])
            ->emptyStateHeading('لا توجد استفسارات')
            ->emptyStateDescription('أضف استفسارات أو أسئلة توضيحية')
            ->emptyStateIcon('heroicon-o-chat-bubble-left-right')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('إضافة استفسار جديد')
                    ->icon('heroicon-o-plus'),
            ]);
    }
}
