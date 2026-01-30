<?php

namespace App\Filament\Resources\TenderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;
use Carbon\Carbon;

class BondsRelationManager extends RelationManager
{
    protected static string $relationship = 'bonds';

    protected static ?string $title = 'الكفالات والضمانات';
    
    protected static ?string $modelLabel = 'كفالة';
    
    protected static ?string $pluralModelLabel = 'الكفالات';
    
    protected static ?string $icon = 'heroicon-o-shield-check';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('نوع الكفالة')
                    ->icon('heroicon-o-document-check')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Select::make('bond_type')
                            ->label('نوع الكفالة')
                            ->options([
                                'bid' => 'كفالة العطاء (ابتدائية)',
                                'performance' => 'كفالة حسن التنفيذ',
                                'advance_payment' => 'كفالة الدفعة المقدمة',
                                'retention' => 'كفالة المحتجزات',
                                'maintenance' => 'كفالة الصيانة',
                            ])
                            ->required()
                            ->native(false)
                            ->live(),
                            
                        Forms\Components\TextInput::make('bond_number')
                            ->label('رقم الكفالة')
                            ->maxLength(100)
                            ->placeholder('رقم خطاب الضمان'),
                            
                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options([
                                'draft' => 'مسودة',
                                'requested' => 'مطلوبة',
                                'active' => 'فعالة',
                                'extended' => 'ممددة',
                                'expired' => 'منتهية',
                                'released' => 'محررة',
                                'claimed' => 'مطالب بها',
                            ])
                            ->default('draft')
                            ->native(false)
                            ->required(),
                    ]),
                    
                Forms\Components\Section::make('البنك والمبلغ')
                    ->icon('heroicon-o-building-library')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Select::make('bank_id')
                            ->label('البنك')
                            ->relationship('bank', 'name_ar')
                            ->searchable()
                            ->preload()
                            ->placeholder('اختر البنك')
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name_ar')
                                    ->label('اسم البنك')
                                    ->required(),
                                Forms\Components\TextInput::make('swift_code')
                                    ->label('رمز SWIFT'),
                            ]),
                            
                        Forms\Components\TextInput::make('amount')
                            ->label('مبلغ الكفالة')
                            ->numeric()
                            ->prefix('د.أ')
                            ->required()
                            ->minValue(0),
                            
                        Forms\Components\TextInput::make('percentage')
                            ->label('النسبة من قيمة العقد')
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->placeholder('مثال: 5'),
                    ]),
                    
                Forms\Components\Section::make('التواريخ')
                    ->icon('heroicon-o-calendar')
                    ->columns(4)
                    ->schema([
                        Forms\Components\DatePicker::make('request_date')
                            ->label('تاريخ الطلب')
                            ->native(false),
                            
                        Forms\Components\DatePicker::make('issue_date')
                            ->label('تاريخ الإصدار')
                            ->native(false)
                            ->required(),
                            
                        Forms\Components\DatePicker::make('expiry_date')
                            ->label('تاريخ الانتهاء')
                            ->native(false)
                            ->required()
                            ->afterOrEqual('issue_date'),
                            
                        Forms\Components\DatePicker::make('release_date')
                            ->label('تاريخ التحرير')
                            ->native(false),
                    ]),
                    
                Forms\Components\Section::make('التمديد')
                    ->icon('heroicon-o-arrow-path')
                    ->columns(3)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\Toggle::make('is_extended')
                            ->label('تم التمديد')
                            ->inline(false)
                            ->live(),
                            
                        Forms\Components\DatePicker::make('extension_date')
                            ->label('تاريخ التمديد')
                            ->native(false)
                            ->visible(fn (Get $get) => $get('is_extended')),
                            
                        Forms\Components\DatePicker::make('new_expiry_date')
                            ->label('تاريخ الانتهاء الجديد')
                            ->native(false)
                            ->visible(fn (Get $get) => $get('is_extended')),
                            
                        Forms\Components\Textarea::make('extension_reason')
                            ->label('سبب التمديد')
                            ->rows(2)
                            ->visible(fn (Get $get) => $get('is_extended'))
                            ->columnSpanFull(),
                    ]),
                    
                Forms\Components\Section::make('معلومات إضافية')
                    ->collapsed()
                    ->schema([
                        Forms\Components\TextInput::make('bank_fees')
                            ->label('رسوم البنك')
                            ->numeric()
                            ->prefix('د.أ'),
                            
                        Forms\Components\TextInput::make('bank_commission_rate')
                            ->label('نسبة عمولة البنك')
                            ->numeric()
                            ->suffix('%'),
                            
                        Forms\Components\FileUpload::make('document_path')
                            ->label('صورة الكفالة')
                            ->directory('bonds')
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->columnSpanFull(),
                            
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('bond_number')
            ->defaultSort('expiry_date')
            ->columns([
                Tables\Columns\TextColumn::make('bond_type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'bid' => 'ابتدائية',
                        'performance' => 'حسن تنفيذ',
                        'advance_payment' => 'دفعة مقدمة',
                        'retention' => 'محتجزات',
                        'maintenance' => 'صيانة',
                        default => $state,
                    })
                    ->color(fn ($state) => match($state) {
                        'bid' => 'info',
                        'performance' => 'success',
                        'advance_payment' => 'warning',
                        'retention' => 'gray',
                        'maintenance' => 'purple',
                        default => 'gray',
                    })
                    ->icon(fn ($state) => match($state) {
                        'bid' => 'heroicon-o-ticket',
                        'performance' => 'heroicon-o-shield-check',
                        'advance_payment' => 'heroicon-o-banknotes',
                        'retention' => 'heroicon-o-lock-closed',
                        'maintenance' => 'heroicon-o-wrench-screwdriver',
                        default => 'heroicon-o-document',
                    }),
                    
                Tables\Columns\TextColumn::make('bond_number')
                    ->label('رقم الكفالة')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('تم نسخ الرقم'),
                    
                Tables\Columns\TextColumn::make('bank.name_ar')
                    ->label('البنك')
                    ->searchable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('amount')
                    ->label('المبلغ')
                    ->money('JOD')
                    ->sortable()
                    ->weight('bold')
                    ->color('success')
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('JOD')
                            ->label('المجموع'),
                    ]),
                    
                Tables\Columns\TextColumn::make('issue_date')
                    ->label('الإصدار')
                    ->date('Y-m-d')
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('expiry_date')
                    ->label('الانتهاء')
                    ->date('Y-m-d')
                    ->sortable()
                    ->color(fn ($record) => 
                        Carbon::parse($record->expiry_date)->isPast() 
                            ? 'danger' 
                            : (Carbon::parse($record->expiry_date)->diffInDays(now()) <= 30 ? 'warning' : 'success')
                    )
                    ->description(fn ($record) => 
                        Carbon::parse($record->expiry_date)->isPast() 
                            ? 'منتهية' 
                            : 'باقي ' . Carbon::parse($record->expiry_date)->diffInDays(now()) . ' يوم'
                    ),
                    
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'draft' => 'مسودة',
                        'requested' => 'مطلوبة',
                        'active' => 'فعالة',
                        'extended' => 'ممددة',
                        'expired' => 'منتهية',
                        'released' => 'محررة',
                        'claimed' => 'مطالب بها',
                        default => $state,
                    })
                    ->color(fn ($state) => match($state) {
                        'draft' => 'gray',
                        'requested' => 'info',
                        'active' => 'success',
                        'extended' => 'warning',
                        'expired' => 'danger',
                        'released' => 'success',
                        'claimed' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn ($state) => match($state) {
                        'active' => 'heroicon-o-check-circle',
                        'released' => 'heroicon-o-check-badge',
                        'expired' => 'heroicon-o-exclamation-circle',
                        'claimed' => 'heroicon-o-exclamation-triangle',
                        default => null,
                    }),
                    
                Tables\Columns\IconColumn::make('is_extended')
                    ->label('ممددة')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('bond_type')
                    ->label('نوع الكفالة')
                    ->options([
                        'bid' => 'كفالة العطاء',
                        'performance' => 'كفالة حسن التنفيذ',
                        'advance_payment' => 'كفالة الدفعة المقدمة',
                        'retention' => 'كفالة المحتجزات',
                        'maintenance' => 'كفالة الصيانة',
                    ]),
                    
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'draft' => 'مسودة',
                        'requested' => 'مطلوبة',
                        'active' => 'فعالة',
                        'extended' => 'ممددة',
                        'expired' => 'منتهية',
                        'released' => 'محررة',
                        'claimed' => 'مطالب بها',
                    ]),
                    
                Tables\Filters\Filter::make('expiring_soon')
                    ->label('تنتهي قريباً (30 يوم)')
                    ->query(fn ($query) => $query
                        ->where('expiry_date', '>', now())
                        ->where('expiry_date', '<=', now()->addDays(30))
                        ->where('status', 'active')
                    )
                    ->toggle(),
                    
                Tables\Filters\Filter::make('expired')
                    ->label('منتهية')
                    ->query(fn ($query) => $query->where('expiry_date', '<', now()))
                    ->toggle(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->icon('heroicon-o-plus')
                    ->label('إضافة كفالة'),
                    
                Tables\Actions\Action::make('add_bid_bond')
                    ->label('كفالة ابتدائية')
                    ->icon('heroicon-o-ticket')
                    ->color('info')
                    ->form([
                        Forms\Components\Select::make('bank_id')
                            ->label('البنك')
                            ->relationship('bank', 'name_ar')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('amount')
                            ->label('المبلغ')
                            ->numeric()
                            ->prefix('د.أ')
                            ->required(),
                        Forms\Components\DatePicker::make('issue_date')
                            ->label('تاريخ الإصدار')
                            ->default(now())
                            ->required(),
                        Forms\Components\DatePicker::make('expiry_date')
                            ->label('تاريخ الانتهاء')
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $tender = $this->getOwnerRecord();
                        $tender->bonds()->create([
                            ...$data,
                            'bond_type' => 'bid',
                            'status' => 'active',
                        ]);
                        Notification::make()->title('تمت إضافة الكفالة')->success()->send();
                    }),
                    
                Tables\Actions\Action::make('expiry_alerts')
                    ->label('تنبيهات الانتهاء')
                    ->icon('heroicon-o-bell-alert')
                    ->color('warning')
                    ->modalHeading('الكفالات المنتهية أو قريبة الانتهاء')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('إغلاق')
                    ->modalContent(function () {
                        $tender = $this->getOwnerRecord();
                        $bonds = $tender->bonds()->where('status', 'active')->get();
                        
                        $expired = $bonds->filter(fn ($b) => Carbon::parse($b->expiry_date)->isPast());
                        $expiringSoon = $bonds->filter(fn ($b) => 
                            !Carbon::parse($b->expiry_date)->isPast() && 
                            Carbon::parse($b->expiry_date)->diffInDays(now()) <= 30
                        );
                        
                        $html = "<div class='space-y-4'>";
                        
                        if ($expired->count() > 0) {
                            $html .= "<div class='bg-danger-50 dark:bg-danger-900/20 p-4 rounded-lg'>
                                <div class='font-bold text-danger-600 mb-2'>⚠️ كفالات منتهية ({$expired->count()})</div>";
                            foreach ($expired as $bond) {
                                $html .= "<div class='text-sm'>• {$bond->bond_number} - " . number_format($bond->amount, 3) . " د.أ</div>";
                            }
                            $html .= "</div>";
                        }
                        
                        if ($expiringSoon->count() > 0) {
                            $html .= "<div class='bg-warning-50 dark:bg-warning-900/20 p-4 rounded-lg'>
                                <div class='font-bold text-warning-600 mb-2'>⏰ تنتهي خلال 30 يوم ({$expiringSoon->count()})</div>";
                            foreach ($expiringSoon as $bond) {
                                $days = Carbon::parse($bond->expiry_date)->diffInDays(now());
                                $html .= "<div class='text-sm'>• {$bond->bond_number} - باقي {$days} يوم</div>";
                            }
                            $html .= "</div>";
                        }
                        
                        if ($expired->count() == 0 && $expiringSoon->count() == 0) {
                            $html .= "<div class='bg-success-50 dark:bg-success-900/20 p-4 rounded-lg text-center'>
                                <div class='text-success-600'>✓ لا توجد كفالات تحتاج متابعة</div>
                            </div>";
                        }
                        
                        $html .= "</div>";
                        return new HtmlString($html);
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    
                    Tables\Actions\Action::make('extend')
                        ->label('تمديد')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->visible(fn ($record) => in_array($record->status, ['active', 'extended']))
                        ->form([
                            Forms\Components\DatePicker::make('new_expiry_date')
                                ->label('تاريخ الانتهاء الجديد')
                                ->required()
                                ->native(false),
                            Forms\Components\Textarea::make('extension_reason')
                                ->label('سبب التمديد')
                                ->rows(2),
                        ])
                        ->action(function ($record, array $data) {
                            $record->update([
                                'is_extended' => true,
                                'extension_date' => now(),
                                'new_expiry_date' => $data['new_expiry_date'],
                                'expiry_date' => $data['new_expiry_date'],
                                'extension_reason' => $data['extension_reason'] ?? null,
                                'status' => 'extended',
                            ]);
                            Notification::make()->title('تم تمديد الكفالة')->success()->send();
                        }),
                        
                    Tables\Actions\Action::make('release')
                        ->label('تحرير')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->visible(fn ($record) => in_array($record->status, ['active', 'extended', 'expired']))
                        ->requiresConfirmation()
                        ->modalDescription('هل تريد تحرير هذه الكفالة؟')
                        ->action(function ($record) {
                            $record->update([
                                'status' => 'released',
                                'release_date' => now(),
                            ]);
                            Notification::make()->title('تم تحرير الكفالة')->success()->send();
                        }),
                        
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('لا توجد كفالات')
            ->emptyStateDescription('أضف الكفالات والضمانات البنكية للعطاء')
            ->emptyStateIcon('heroicon-o-shield-check')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('إضافة كفالة جديدة')
                    ->icon('heroicon-o-plus'),
            ]);
    }
}
