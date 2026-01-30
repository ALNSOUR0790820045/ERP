<?php

namespace App\Filament\Resources\TenderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;

class CompetitorsRelationManager extends RelationManager
{
    protected static string $relationship = 'competitors';

    protected static ?string $title = 'المنافسون';
    
    protected static ?string $modelLabel = 'منافس';
    
    protected static ?string $pluralModelLabel = 'المنافسون';
    
    protected static ?string $icon = 'heroicon-o-users';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات المنافس')
                    ->icon('heroicon-o-building-office')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('company_name')
                            ->label('اسم الشركة')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('اسم الشركة المنافسة'),
                            
                        Forms\Components\TextInput::make('registration_number')
                            ->label('رقم التسجيل')
                            ->maxLength(100)
                            ->placeholder('رقم السجل التجاري'),
                            
                        Forms\Components\Select::make('company_size')
                            ->label('حجم الشركة')
                            ->options([
                                'micro' => 'صغيرة جداً',
                                'small' => 'صغيرة',
                                'medium' => 'متوسطة',
                                'large' => 'كبيرة',
                                'enterprise' => 'عملاقة',
                            ])
                            ->native(false),
                            
                        Forms\Components\Select::make('classification')
                            ->label('التصنيف')
                            ->options([
                                'first' => 'الأولى',
                                'second' => 'الثانية',
                                'third' => 'الثالثة',
                                'fourth' => 'الرابعة',
                                'fifth' => 'الخامسة',
                            ])
                            ->native(false),
                    ]),
                    
                Forms\Components\Section::make('العرض المقدم')
                    ->icon('heroicon-o-banknotes')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('submitted_price')
                            ->label('السعر المقدم')
                            ->numeric()
                            ->prefix('د.أ')
                            ->placeholder('سعر العرض'),
                            
                        Forms\Components\TextInput::make('technical_score')
                            ->label('الدرجة الفنية')
                            ->numeric()
                            ->suffix('/100')
                            ->minValue(0)
                            ->maxValue(100),
                            
                        Forms\Components\TextInput::make('financial_score')
                            ->label('الدرجة المالية')
                            ->numeric()
                            ->suffix('/100')
                            ->minValue(0)
                            ->maxValue(100),
                            
                        Forms\Components\TextInput::make('rank')
                            ->label('الترتيب')
                            ->numeric()
                            ->minValue(1),
                            
                        Forms\Components\TextInput::make('discount_percentage')
                            ->label('نسبة الخصم')
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100),
                            
                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options([
                                'registered' => 'مسجل',
                                'qualified' => 'مؤهل',
                                'disqualified' => 'غير مؤهل',
                                'lowest' => 'أقل سعر',
                                'winner' => 'فائز',
                            ])
                            ->default('registered')
                            ->native(false),
                    ]),
                    
                Forms\Components\Section::make('معلومات إضافية')
                    ->collapsible()
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('contact_person')
                            ->label('شخص التواصل')
                            ->maxLength(255),
                            
                        Forms\Components\TextInput::make('contact_phone')
                            ->label('رقم الهاتف')
                            ->tel()
                            ->maxLength(20),
                            
                        Forms\Components\Textarea::make('strengths')
                            ->label('نقاط القوة')
                            ->rows(2)
                            ->placeholder('ما يميز هذا المنافس'),
                            
                        Forms\Components\Textarea::make('weaknesses')
                            ->label('نقاط الضعف')
                            ->rows(2)
                            ->placeholder('نقاط الضعف المعروفة'),
                            
                        Forms\Components\Textarea::make('disqualification_reason')
                            ->label('سبب عدم التأهيل')
                            ->rows(2)
                            ->columnSpanFull(),
                            
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('company_name')
            ->defaultSort('rank')
            ->columns([
                Tables\Columns\TextColumn::make('rank')
                    ->label('#')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        1 => 'success',
                        2 => 'info',
                        3 => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => $state ?: '-'),
                    
                Tables\Columns\TextColumn::make('company_name')
                    ->label('اسم الشركة')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record) => $record->classification ? 'الفئة ' . match($record->classification) {
                        'first' => 'الأولى',
                        'second' => 'الثانية',
                        'third' => 'الثالثة',
                        'fourth' => 'الرابعة',
                        'fifth' => 'الخامسة',
                        default => $record->classification,
                    } : null),
                    
                Tables\Columns\TextColumn::make('submitted_price')
                    ->label('السعر المقدم')
                    ->money('JOD')
                    ->sortable()
                    ->alignEnd()
                    ->weight('bold')
                    ->color(fn ($record) => $record->status === 'winner' ? 'success' : ($record->rank === 1 ? 'primary' : 'gray')),
                    
                Tables\Columns\TextColumn::make('price_comparison')
                    ->label('مقارنة السعر')
                    ->getStateUsing(function ($record) {
                        $tender = $this->getOwnerRecord();
                        $ourPrice = $tender->submitted_price ?? $tender->estimated_value;
                        if (!$ourPrice || !$record->submitted_price) return '-';
                        
                        $diff = (($record->submitted_price - $ourPrice) / $ourPrice) * 100;
                        return ($diff >= 0 ? '+' : '') . number_format($diff, 1) . '%';
                    })
                    ->badge()
                    ->color(fn ($state) => 
                        $state === '-' ? 'gray' : (str_starts_with($state, '+') ? 'success' : 'danger')
                    )
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('technical_score')
                    ->label('الفني')
                    ->formatStateUsing(fn ($state) => $state ? $state . '/100' : '-')
                    ->alignCenter()
                    ->color(fn ($state) => 
                        !$state ? 'gray' : ($state >= 70 ? 'success' : ($state >= 50 ? 'warning' : 'danger'))
                    )
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('financial_score')
                    ->label('المالي')
                    ->formatStateUsing(fn ($state) => $state ? $state . '/100' : '-')
                    ->alignCenter()
                    ->color(fn ($state) => 
                        !$state ? 'gray' : ($state >= 70 ? 'success' : ($state >= 50 ? 'warning' : 'danger'))
                    )
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('total_score')
                    ->label('المجموع')
                    ->getStateUsing(fn ($record) => 
                        $record->technical_score && $record->financial_score 
                            ? round(($record->technical_score + $record->financial_score) / 2, 1)
                            : null
                    )
                    ->formatStateUsing(fn ($state) => $state ? $state . '/100' : '-')
                    ->badge()
                    ->color(fn ($state) => 
                        !$state ? 'gray' : ($state >= 70 ? 'success' : ($state >= 50 ? 'warning' : 'danger'))
                    )
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'registered' => 'مسجل',
                        'qualified' => 'مؤهل',
                        'disqualified' => 'غير مؤهل',
                        'lowest' => 'أقل سعر',
                        'winner' => 'فائز',
                        default => $state,
                    })
                    ->color(fn ($state) => match($state) {
                        'registered' => 'gray',
                        'qualified' => 'info',
                        'disqualified' => 'danger',
                        'lowest' => 'warning',
                        'winner' => 'success',
                        default => 'gray',
                    })
                    ->icon(fn ($state) => match($state) {
                        'winner' => 'heroicon-o-trophy',
                        'disqualified' => 'heroicon-o-x-circle',
                        'lowest' => 'heroicon-o-arrow-down',
                        default => null,
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'registered' => 'مسجل',
                        'qualified' => 'مؤهل',
                        'disqualified' => 'غير مؤهل',
                        'lowest' => 'أقل سعر',
                        'winner' => 'فائز',
                    ]),
                    
                Tables\Filters\SelectFilter::make('classification')
                    ->label('التصنيف')
                    ->options([
                        'first' => 'الأولى',
                        'second' => 'الثانية',
                        'third' => 'الثالثة',
                        'fourth' => 'الرابعة',
                        'fifth' => 'الخامسة',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->icon('heroicon-o-plus')
                    ->label('إضافة منافس'),
                    
                Tables\Actions\Action::make('quick_add')
                    ->label('إضافة سريعة')
                    ->icon('heroicon-o-bolt')
                    ->color('warning')
                    ->form([
                        Forms\Components\TextInput::make('company_name')
                            ->label('اسم الشركة')
                            ->required(),
                        Forms\Components\TextInput::make('submitted_price')
                            ->label('السعر المقدم')
                            ->numeric()
                            ->prefix('د.أ'),
                    ])
                    ->action(function (array $data) {
                        $tender = $this->getOwnerRecord();
                        $tender->competitors()->create([
                            'company_name' => $data['company_name'],
                            'submitted_price' => $data['submitted_price'],
                            'status' => 'registered',
                        ]);
                        Notification::make()->title('تمت الإضافة')->success()->send();
                    }),
                    
                Tables\Actions\Action::make('auto_rank')
                    ->label('ترتيب تلقائي')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalDescription('سيتم ترتيب المنافسين تلقائياً حسب السعر (الأقل أولاً)')
                    ->action(function () {
                        $tender = $this->getOwnerRecord();
                        $competitors = $tender->competitors()
                            ->whereNotNull('submitted_price')
                            ->orderBy('submitted_price')
                            ->get();
                        
                        $rank = 1;
                        foreach ($competitors as $competitor) {
                            $competitor->update(['rank' => $rank++]);
                        }
                        
                        // Mark lowest as "lowest"
                        if ($competitors->first()) {
                            $competitors->first()->update(['status' => 'lowest']);
                        }
                        
                        Notification::make()->title('تم الترتيب')->success()->send();
                    }),
                    
                Tables\Actions\Action::make('analysis')
                    ->label('تحليل المنافسة')
                    ->icon('heroicon-o-chart-bar')
                    ->color('success')
                    ->modalHeading('تحليل المنافسة')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('إغلاق')
                    ->modalWidth('xl')
                    ->modalContent(function () {
                        $tender = $this->getOwnerRecord();
                        $competitors = $tender->competitors()
                            ->whereNotNull('submitted_price')
                            ->orderBy('submitted_price')
                            ->get();
                        
                        $ourPrice = $tender->submitted_price ?? $tender->estimated_value ?? 0;
                        $lowestPrice = $competitors->min('submitted_price') ?? 0;
                        $highestPrice = $competitors->max('submitted_price') ?? 0;
                        $avgPrice = $competitors->avg('submitted_price') ?? 0;
                        $total = $competitors->count();
                        
                        // Calculate our position
                        $ourRank = $competitors->where('submitted_price', '<', $ourPrice)->count() + 1;
                        
                        $html = "
                            <div class='space-y-6'>
                                <div class='grid grid-cols-4 gap-4 text-center'>
                                    <div class='bg-primary-50 dark:bg-primary-900/20 p-4 rounded-lg'>
                                        <div class='text-2xl font-bold text-primary-600'>{$total}</div>
                                        <div class='text-sm text-gray-600'>عدد المنافسين</div>
                                    </div>
                                    <div class='bg-success-50 dark:bg-success-900/20 p-4 rounded-lg'>
                                        <div class='text-lg font-bold text-success-600'>" . number_format($lowestPrice, 0) . "</div>
                                        <div class='text-sm text-gray-600'>أقل سعر</div>
                                    </div>
                                    <div class='bg-warning-50 dark:bg-warning-900/20 p-4 rounded-lg'>
                                        <div class='text-lg font-bold text-warning-600'>" . number_format($avgPrice, 0) . "</div>
                                        <div class='text-sm text-gray-600'>متوسط السعر</div>
                                    </div>
                                    <div class='bg-danger-50 dark:bg-danger-900/20 p-4 rounded-lg'>
                                        <div class='text-lg font-bold text-danger-600'>" . number_format($highestPrice, 0) . "</div>
                                        <div class='text-sm text-gray-600'>أعلى سعر</div>
                                    </div>
                                </div>
                                
                                <div class='bg-gray-100 dark:bg-gray-800 p-4 rounded-lg'>
                                    <div class='font-bold mb-2'>موقعنا التنافسي</div>
                                    <div class='grid grid-cols-2 gap-4'>
                                        <div>
                                            <span class='text-gray-600'>سعرنا:</span>
                                            <span class='font-bold'>" . number_format($ourPrice, 0) . " د.أ</span>
                                        </div>
                                        <div>
                                            <span class='text-gray-600'>ترتيبنا المتوقع:</span>
                                            <span class='font-bold'>{$ourRank} من " . ($total + 1) . "</span>
                                        </div>
                                    </div>
                                </div>";
                        
                        if ($competitors->count() > 0) {
                            $html .= "<div class='bg-gray-50 dark:bg-gray-900 p-4 rounded-lg'>
                                <div class='font-bold mb-3'>ترتيب الأسعار</div>
                                <div class='space-y-2'>";
                            
                            foreach ($competitors as $i => $c) {
                                $bar = min(100, ($c->submitted_price / $highestPrice) * 100);
                                $html .= "
                                    <div class='flex items-center gap-2'>
                                        <div class='w-8 text-center font-bold'>" . ($i + 1) . "</div>
                                        <div class='flex-1'>
                                            <div class='text-sm'>{$c->company_name}</div>
                                            <div class='bg-gray-200 dark:bg-gray-700 rounded-full h-2'>
                                                <div class='bg-primary-500 h-2 rounded-full' style='width: {$bar}%'></div>
                                            </div>
                                        </div>
                                        <div class='w-24 text-left font-mono text-sm'>" . number_format($c->submitted_price, 0) . "</div>
                                    </div>";
                            }
                            
                            $html .= "</div></div>";
                        }
                        
                        $html .= "</div>";
                        return new HtmlString($html);
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    
                    Tables\Actions\Action::make('mark_winner')
                        ->label('تعيين كفائز')
                        ->icon('heroicon-o-trophy')
                        ->color('success')
                        ->visible(fn ($record) => $record->status !== 'winner')
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            // Remove winner status from others
                            $tender = $this->getOwnerRecord();
                            $tender->competitors()->update(['status' => 'qualified']);
                            
                            // Set this as winner
                            $record->update(['status' => 'winner', 'rank' => 1]);
                            
                            Notification::make()->title('تم تعيين الفائز')->success()->send();
                        }),
                        
                    Tables\Actions\Action::make('disqualify')
                        ->label('استبعاد')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn ($record) => !in_array($record->status, ['disqualified', 'winner']))
                        ->form([
                            Forms\Components\Textarea::make('disqualification_reason')
                                ->label('سبب الاستبعاد')
                                ->required()
                                ->rows(2),
                        ])
                        ->action(function ($record, array $data) {
                            $record->update([
                                'status' => 'disqualified',
                                'disqualification_reason' => $data['disqualification_reason'],
                            ]);
                            Notification::make()->title('تم الاستبعاد')->warning()->send();
                        }),
                        
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('mark_qualified')
                        ->label('تأهيل المحدد')
                        ->icon('heroicon-o-check')
                        ->action(function ($records) {
                            $records->each->update(['status' => 'qualified']);
                            Notification::make()->title('تم التأهيل')->success()->send();
                        }),
                ]),
            ])
            ->emptyStateHeading('لا يوجد منافسون')
            ->emptyStateDescription('أضف المنافسين وأسعارهم بعد فتح المظاريف')
            ->emptyStateIcon('heroicon-o-users')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('إضافة منافس')
                    ->icon('heroicon-o-plus'),
            ]);
    }
}
