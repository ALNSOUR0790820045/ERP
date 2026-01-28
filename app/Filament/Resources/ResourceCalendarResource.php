<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ResourceCalendarResource\Pages;
use App\Models\ResourceCalendar;
use App\Services\ProjectManagement\ResourceCalendarService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ResourceCalendarResource extends Resource
{
    protected static ?string $model = ResourceCalendar::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'تقويمات الموارد';
    protected static ?string $modelLabel = 'تقويم الموارد';
    protected static ?string $pluralModelLabel = 'تقويمات الموارد';
    protected static ?string $navigationGroup = 'المشاريع';
    protected static ?int $navigationSort = 89;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات التقويم')
                    ->schema([
                        Forms\Components\Select::make('project_id')
                            ->label('المشروع')
                            ->relationship('project', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        
                        Forms\Components\TextInput::make('name')
                            ->label('اسم التقويم')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\Textarea::make('description')
                            ->label('الوصف')
                            ->rows(2),
                        
                        Forms\Components\Select::make('calendar_type')
                            ->label('نوع التقويم')
                            ->options([
                                'standard' => 'قياسي',
                                'shift' => 'ورديات',
                                '24_hour' => '24 ساعة',
                                'custom' => 'مخصص',
                            ])
                            ->default('standard')
                            ->required(),
                        
                        Forms\Components\Select::make('time_zone')
                            ->label('المنطقة الزمنية')
                            ->options([
                                'Asia/Amman' => 'عمان (الأردن)',
                                'Asia/Riyadh' => 'الرياض',
                                'Asia/Dubai' => 'دبي',
                                'Africa/Cairo' => 'القاهرة',
                                'UTC' => 'UTC',
                            ])
                            ->default('Asia/Amman')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('أيام العمل')
                    ->schema([
                        Forms\Components\Toggle::make('work_week.sunday')
                            ->label('الأحد')
                            ->default(true),
                        
                        Forms\Components\Toggle::make('work_week.monday')
                            ->label('الاثنين')
                            ->default(true),
                        
                        Forms\Components\Toggle::make('work_week.tuesday')
                            ->label('الثلاثاء')
                            ->default(true),
                        
                        Forms\Components\Toggle::make('work_week.wednesday')
                            ->label('الأربعاء')
                            ->default(true),
                        
                        Forms\Components\Toggle::make('work_week.thursday')
                            ->label('الخميس')
                            ->default(true),
                        
                        Forms\Components\Toggle::make('work_week.friday')
                            ->label('الجمعة')
                            ->default(false),
                        
                        Forms\Components\Toggle::make('work_week.saturday')
                            ->label('السبت')
                            ->default(false),
                    ])->columns(7),

                Forms\Components\Section::make('ساعات العمل')
                    ->schema([
                        Forms\Components\TimePicker::make('work_hours.start')
                            ->label('بداية العمل')
                            ->default('08:00')
                            ->required(),
                        
                        Forms\Components\TimePicker::make('work_hours.end')
                            ->label('نهاية العمل')
                            ->default('16:00')
                            ->required(),
                        
                        Forms\Components\TimePicker::make('work_hours.break_start')
                            ->label('بداية الاستراحة')
                            ->default('12:00'),
                        
                        Forms\Components\TimePicker::make('work_hours.break_end')
                            ->label('نهاية الاستراحة')
                            ->default('13:00'),
                        
                        Forms\Components\TextInput::make('hours_per_day')
                            ->label('ساعات العمل اليومية')
                            ->numeric()
                            ->default(7)
                            ->minValue(1)
                            ->maxValue(24)
                            ->required(),
                        
                        Forms\Components\TextInput::make('hours_per_week')
                            ->label('ساعات العمل الأسبوعية')
                            ->numeric()
                            ->default(35)
                            ->minValue(1)
                            ->maxValue(168)
                            ->required(),
                    ])->columns(3),

                Forms\Components\Section::make('الاستثناءات والعطل')
                    ->schema([
                        Forms\Components\Repeater::make('exceptions')
                            ->relationship('exceptions')
                            ->label('الاستثناءات')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('الاسم')
                                    ->required(),
                                
                                Forms\Components\Select::make('exception_type')
                                    ->label('النوع')
                                    ->options([
                                        'holiday' => 'عطلة رسمية',
                                        'vacation' => 'إجازة',
                                        'special_work' => 'يوم عمل خاص',
                                        'overtime' => 'عمل إضافي',
                                    ])
                                    ->default('holiday')
                                    ->required(),
                                
                                Forms\Components\DatePicker::make('start_date')
                                    ->label('تاريخ البداية')
                                    ->required(),
                                
                                Forms\Components\DatePicker::make('end_date')
                                    ->label('تاريخ النهاية')
                                    ->required(),
                                
                                Forms\Components\Toggle::make('is_working')
                                    ->label('يوم عمل')
                                    ->default(false),
                                
                                Forms\Components\Toggle::make('is_recurring')
                                    ->label('متكرر سنوياً')
                                    ->default(false),
                                
                                Forms\Components\Textarea::make('notes')
                                    ->label('ملاحظات')
                                    ->rows(1),
                            ])
                            ->columns(4)
                            ->collapsible()
                            ->defaultItems(0),
                    ]),

                Forms\Components\Section::make('الإعدادات')
                    ->schema([
                        Forms\Components\Toggle::make('is_default')
                            ->label('تقويم افتراضي')
                            ->default(false),
                        
                        Forms\Components\Toggle::make('is_active')
                            ->label('نشط')
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('project.name')
                    ->label('المشروع')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم التقويم')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('calendar_type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'standard' => 'قياسي',
                        'shift' => 'ورديات',
                        '24_hour' => '24 ساعة',
                        'custom' => 'مخصص',
                        default => $state,
                    }),
                
                Tables\Columns\TextColumn::make('hours_per_day')
                    ->label('ساعات/يوم')
                    ->suffix(' س'),
                
                Tables\Columns\TextColumn::make('hours_per_week')
                    ->label('ساعات/أسبوع')
                    ->suffix(' س'),
                
                Tables\Columns\TextColumn::make('exceptions_count')
                    ->label('الاستثناءات')
                    ->counts('exceptions'),
                
                Tables\Columns\TextColumn::make('assignments_count')
                    ->label('الموارد المعينة')
                    ->counts('assignments'),
                
                Tables\Columns\IconColumn::make('is_default')
                    ->label('افتراضي')
                    ->boolean(),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('project_id')
                    ->label('المشروع')
                    ->relationship('project', 'name'),
                
                Tables\Filters\SelectFilter::make('calendar_type')
                    ->label('النوع')
                    ->options([
                        'standard' => 'قياسي',
                        'shift' => 'ورديات',
                        '24_hour' => '24 ساعة',
                        'custom' => 'مخصص',
                    ]),
                
                Tables\Filters\TernaryFilter::make('is_default')
                    ->label('افتراضي'),
                
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('نشط'),
            ])
            ->actions([
                Tables\Actions\Action::make('add_holidays')
                    ->label('إضافة عطل الأردن')
                    ->icon('heroicon-o-calendar')
                    ->color('info')
                    ->action(function (ResourceCalendar $record) {
                        try {
                            $service = app(ResourceCalendarService::class);
                            $service->addJordanianHolidays($record->id, now()->year);
                            
                            Notification::make()
                                ->success()
                                ->title('تمت إضافة العطل الرسمية')
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('خطأ')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
                
                Tables\Actions\Action::make('copy')
                    ->label('نسخ')
                    ->icon('heroicon-o-document-duplicate')
                    ->form([
                        Forms\Components\TextInput::make('new_name')
                            ->label('اسم التقويم الجديد')
                            ->required(),
                    ])
                    ->action(function (ResourceCalendar $record, array $data) {
                        try {
                            $service = app(ResourceCalendarService::class);
                            $service->copyCalendar($record->id, $data['new_name']);
                            
                            Notification::make()
                                ->success()
                                ->title('تم نسخ التقويم بنجاح')
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('خطأ')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
                
                Tables\Actions\Action::make('validate')
                    ->label('التحقق')
                    ->icon('heroicon-o-check-circle')
                    ->action(function (ResourceCalendar $record) {
                        $service = app(ResourceCalendarService::class);
                        $result = $service->validateCalendar($record->id);
                        
                        if ($result['valid']) {
                            Notification::make()
                                ->success()
                                ->title('التقويم صالح')
                                ->body(count($result['warnings']) > 0 ? implode("\n", $result['warnings']) : 'لا توجد مشاكل')
                                ->send();
                        } else {
                            Notification::make()
                                ->danger()
                                ->title('التقويم غير صالح')
                                ->body(implode("\n", $result['errors']))
                                ->send();
                        }
                    }),
                
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('إحصائيات التقويم')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('hours_per_day')
                                    ->label('ساعات/يوم')
                                    ->suffix(' ساعة'),
                                
                                Infolists\Components\TextEntry::make('hours_per_week')
                                    ->label('ساعات/أسبوع')
                                    ->suffix(' ساعة'),
                                
                                Infolists\Components\TextEntry::make('exceptions_count')
                                    ->label('عدد الاستثناءات')
                                    ->getStateUsing(fn (ResourceCalendar $record) => $record->exceptions()->count()),
                                
                                Infolists\Components\TextEntry::make('assignments_count')
                                    ->label('الموارد المعينة')
                                    ->getStateUsing(fn (ResourceCalendar $record) => $record->assignments()->count()),
                            ]),
                    ]),

                Infolists\Components\Section::make('أيام العمل')
                    ->schema([
                        Infolists\Components\TextEntry::make('work_week')
                            ->label('')
                            ->getStateUsing(function (ResourceCalendar $record) {
                                $days = [
                                    'sunday' => 'الأحد',
                                    'monday' => 'الاثنين',
                                    'tuesday' => 'الثلاثاء',
                                    'wednesday' => 'الأربعاء',
                                    'thursday' => 'الخميس',
                                    'friday' => 'الجمعة',
                                    'saturday' => 'السبت',
                                ];
                                
                                $workDays = [];
                                foreach ($record->work_week ?? [] as $day => $isWorking) {
                                    if ($isWorking) {
                                        $workDays[] = $days[$day] ?? $day;
                                    }
                                }
                                
                                return implode('، ', $workDays);
                            }),
                    ]),

                Infolists\Components\Section::make('ساعات العمل')
                    ->schema([
                        Infolists\Components\TextEntry::make('work_hours')
                            ->label('')
                            ->getStateUsing(function (ResourceCalendar $record) {
                                $hours = $record->work_hours ?? [];
                                return sprintf(
                                    'من %s إلى %s (استراحة: %s - %s)',
                                    $hours['start'] ?? '08:00',
                                    $hours['end'] ?? '16:00',
                                    $hours['break_start'] ?? '12:00',
                                    $hours['break_end'] ?? '13:00'
                                );
                            }),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListResourceCalendars::route('/'),
            'create' => Pages\CreateResourceCalendar::route('/create'),
            'view' => Pages\ViewResourceCalendar::route('/{record}'),
            'edit' => Pages\EditResourceCalendar::route('/{record}/edit'),
        ];
    }
}
