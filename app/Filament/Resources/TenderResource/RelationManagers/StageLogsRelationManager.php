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

class StageLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'stageLogs';

    protected static ?string $title = 'سجل المراحل والأنشطة';
    
    protected static ?string $modelLabel = 'سجل';
    
    protected static ?string $pluralModelLabel = 'سجل المراحل';
    
    protected static ?string $icon = 'heroicon-o-clock';

    public function isReadOnly(): bool
    {
        return false; // Allow adding manual logs
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('تفاصيل السجل')
                    ->icon('heroicon-o-document-text')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Select::make('stage')
                            ->label('المرحلة')
                            ->options([
                                'discovery' => 'الرصد والتسجيل',
                                'evaluation' => 'الدراسة والقرار',
                                'preparation' => 'إعداد العرض',
                                'submission' => 'التقديم',
                                'opening' => 'الفتح والنتائج',
                                'award' => 'الترسية والتحويل',
                            ])
                            ->required()
                            ->native(false),
                            
                        Forms\Components\Select::make('action')
                            ->label('الإجراء')
                            ->options([
                                'started' => 'بدء المرحلة',
                                'completed' => 'إكمال المرحلة',
                                'decision' => 'قرار',
                                'update' => 'تحديث',
                                'note' => 'ملاحظة',
                                'alert' => 'تنبيه',
                                'milestone' => 'نقطة مهمة',
                            ])
                            ->default('note')
                            ->native(false),
                            
                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options([
                                'info' => 'معلومات',
                                'success' => 'نجاح',
                                'warning' => 'تحذير',
                                'danger' => 'خطر',
                            ])
                            ->default('info')
                            ->native(false),
                    ]),
                    
                Forms\Components\Section::make('التفاصيل')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('العنوان')
                            ->maxLength(255)
                            ->placeholder('عنوان مختصر للسجل'),
                            
                        Forms\Components\Textarea::make('notes')
                            ->label('التفاصيل')
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('تفاصيل الإجراء أو الملاحظة'),
                            
                        Forms\Components\DateTimePicker::make('created_at')
                            ->label('التاريخ والوقت')
                            ->default(now())
                            ->native(false),
                    ])->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('stage')
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('التاريخ')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->description(fn ($record) => Carbon::parse($record->created_at)->diffForHumans()),
                    
                Tables\Columns\TextColumn::make('stage')
                    ->label('المرحلة')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'discovery' => '📌 الرصد',
                        'evaluation' => '🔍 الدراسة',
                        'preparation' => '📝 الإعداد',
                        'submission' => '📤 التقديم',
                        'opening' => '📂 الفتح',
                        'award' => '🏆 الترسية',
                        default => $state,
                    })
                    ->color(fn ($state) => match($state) {
                        'discovery' => 'gray',
                        'evaluation' => 'info',
                        'preparation' => 'warning',
                        'submission' => 'primary',
                        'opening' => 'purple',
                        'award' => 'success',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('action')
                    ->label('الإجراء')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'started' => '▶️ بدء',
                        'completed' => '✅ إكمال',
                        'decision' => '⚖️ قرار',
                        'update' => '🔄 تحديث',
                        'note' => '📝 ملاحظة',
                        'alert' => '⚠️ تنبيه',
                        'milestone' => '🎯 نقطة مهمة',
                        default => $state,
                    })
                    ->color(fn ($state) => match($state) {
                        'started' => 'info',
                        'completed' => 'success',
                        'decision' => 'warning',
                        'update' => 'gray',
                        'note' => 'gray',
                        'alert' => 'danger',
                        'milestone' => 'primary',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable()
                    ->limit(30)
                    ->weight('medium'),
                    
                Tables\Columns\TextColumn::make('notes')
                    ->label('التفاصيل')
                    ->limit(50)
                    ->wrap()
                    ->tooltip(fn ($state) => $state)
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'info' => 'ℹ️',
                        'success' => '✓',
                        'warning' => '⚠',
                        'danger' => '✗',
                        default => '',
                    })
                    ->color(fn ($state) => $state ?? 'gray'),
                    
                Tables\Columns\TextColumn::make('completedBy.name')
                    ->label('بواسطة')
                    ->toggleable()
                    ->placeholder('النظام'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('stage')
                    ->label('المرحلة')
                    ->options([
                        'discovery' => 'الرصد والتسجيل',
                        'evaluation' => 'الدراسة والقرار',
                        'preparation' => 'إعداد العرض',
                        'submission' => 'التقديم',
                        'opening' => 'الفتح والنتائج',
                        'award' => 'الترسية والتحويل',
                    ]),
                    
                Tables\Filters\SelectFilter::make('action')
                    ->label('الإجراء')
                    ->options([
                        'started' => 'بدء المرحلة',
                        'completed' => 'إكمال المرحلة',
                        'decision' => 'قرار',
                        'update' => 'تحديث',
                        'note' => 'ملاحظة',
                        'alert' => 'تنبيه',
                        'milestone' => 'نقطة مهمة',
                    ]),
                    
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'info' => 'معلومات',
                        'success' => 'نجاح',
                        'warning' => 'تحذير',
                        'danger' => 'خطر',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->icon('heroicon-o-plus')
                    ->label('إضافة سجل'),
                    
                Tables\Actions\Action::make('add_note')
                    ->label('إضافة ملاحظة')
                    ->icon('heroicon-o-chat-bubble-left')
                    ->color('gray')
                    ->form([
                        Forms\Components\Select::make('stage')
                            ->label('المرحلة')
                            ->options([
                                'discovery' => 'الرصد',
                                'evaluation' => 'الدراسة',
                                'preparation' => 'الإعداد',
                                'submission' => 'التقديم',
                                'opening' => 'الفتح',
                                'award' => 'الترسية',
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('notes')
                            ->label('الملاحظة')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (array $data) {
                        $tender = $this->getOwnerRecord();
                        $tender->stageLogs()->create([
                            'stage' => $data['stage'],
                            'action' => 'note',
                            'notes' => $data['notes'],
                            'status' => 'info',
                            'completed_by' => auth()->id(),
                        ]);
                        Notification::make()->title('تمت إضافة الملاحظة')->success()->send();
                    }),
                    
                Tables\Actions\Action::make('add_alert')
                    ->label('إضافة تنبيه')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('danger')
                    ->form([
                        Forms\Components\Select::make('stage')
                            ->label('المرحلة')
                            ->options([
                                'discovery' => 'الرصد',
                                'evaluation' => 'الدراسة',
                                'preparation' => 'الإعداد',
                                'submission' => 'التقديم',
                                'opening' => 'الفتح',
                                'award' => 'الترسية',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('title')
                            ->label('عنوان التنبيه')
                            ->required(),
                        Forms\Components\Textarea::make('notes')
                            ->label('التفاصيل')
                            ->rows(2),
                    ])
                    ->action(function (array $data) {
                        $tender = $this->getOwnerRecord();
                        $tender->stageLogs()->create([
                            'stage' => $data['stage'],
                            'action' => 'alert',
                            'title' => $data['title'],
                            'notes' => $data['notes'] ?? null,
                            'status' => 'danger',
                            'completed_by' => auth()->id(),
                        ]);
                        Notification::make()->title('تم إضافة التنبيه')->warning()->send();
                    }),
                    
                Tables\Actions\Action::make('timeline')
                    ->label('الجدول الزمني')
                    ->icon('heroicon-o-calendar-days')
                    ->color('info')
                    ->modalHeading('الجدول الزمني للعطاء')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('إغلاق')
                    ->modalWidth('xl')
                    ->modalContent(function () {
                        $tender = $this->getOwnerRecord();
                        $logs = $tender->stageLogs()->orderBy('created_at')->get();
                        
                        $html = "<div class='relative'>";
                        
                        // Timeline line
                        $html .= "<div class='absolute right-4 top-0 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-700'></div>";
                        
                        $html .= "<div class='space-y-4 pr-10'>";
                        
                        $stageColors = [
                            'discovery' => 'bg-gray-500',
                            'evaluation' => 'bg-blue-500',
                            'preparation' => 'bg-yellow-500',
                            'submission' => 'bg-purple-500',
                            'opening' => 'bg-pink-500',
                            'award' => 'bg-green-500',
                        ];
                        
                        $stageNames = [
                            'discovery' => 'الرصد',
                            'evaluation' => 'الدراسة',
                            'preparation' => 'الإعداد',
                            'submission' => 'التقديم',
                            'opening' => 'الفتح',
                            'award' => 'الترسية',
                        ];
                        
                        $actionIcons = [
                            'started' => '▶️',
                            'completed' => '✅',
                            'decision' => '⚖️',
                            'update' => '🔄',
                            'note' => '📝',
                            'alert' => '⚠️',
                            'milestone' => '🎯',
                        ];
                        
                        foreach ($logs as $log) {
                            $color = $stageColors[$log->stage] ?? 'bg-gray-500';
                            $stageName = $stageNames[$log->stage] ?? $log->stage;
                            $icon = $actionIcons[$log->action] ?? '•';
                            $date = Carbon::parse($log->created_at)->format('Y-m-d H:i');
                            
                            $html .= "
                                <div class='relative flex gap-4'>
                                    <div class='absolute right-0 w-3 h-3 rounded-full {$color} ring-4 ring-white dark:ring-gray-900' style='right: -26px; top: 6px;'></div>
                                    <div class='flex-1 bg-gray-50 dark:bg-gray-800 rounded-lg p-3'>
                                        <div class='flex justify-between items-start'>
                                            <div>
                                                <span class='text-xs text-gray-500'>{$stageName}</span>
                                                <div class='font-medium'>{$icon} " . ($log->title ?? ($log->notes ? mb_substr($log->notes, 0, 50) : 'سجل')) . "</div>
                                            </div>
                                            <div class='text-xs text-gray-400'>{$date}</div>
                                        </div>
                                        " . ($log->notes ? "<div class='text-sm text-gray-600 mt-1'>{$log->notes}</div>" : "") . "
                                    </div>
                                </div>
                            ";
                        }
                        
                        if ($logs->count() === 0) {
                            $html .= "<div class='text-center text-gray-500 py-8'>لا توجد سجلات بعد</div>";
                        }
                        
                        $html .= "</div></div>";
                        return new HtmlString($html);
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('لا توجد سجلات')
            ->emptyStateDescription('سيتم تسجيل جميع الأنشطة والتغييرات تلقائياً')
            ->emptyStateIcon('heroicon-o-clock')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('إضافة سجل يدوي')
                    ->icon('heroicon-o-plus'),
            ]);
    }
}
