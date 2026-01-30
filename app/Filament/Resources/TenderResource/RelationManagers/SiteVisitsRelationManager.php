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

class SiteVisitsRelationManager extends RelationManager
{
    protected static string $relationship = 'siteVisits';

    protected static ?string $title = 'زيارات الموقع';
    
    protected static ?string $modelLabel = 'زيارة';
    
    protected static ?string $pluralModelLabel = 'زيارات الموقع';
    
    protected static ?string $icon = 'heroicon-o-map-pin';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('تفاصيل الزيارة')
                    ->icon('heroicon-o-calendar')
                    ->columns(3)
                    ->schema([
                        Forms\Components\DateTimePicker::make('visit_date')
                            ->label('تاريخ ووقت الزيارة')
                            ->required()
                            ->native(false)
                            ->displayFormat('Y-m-d H:i')
                            ->seconds(false),
                            
                        Forms\Components\Select::make('visit_type')
                            ->label('نوع الزيارة')
                            ->options([
                                'mandatory' => 'إلزامية',
                                'optional' => 'اختيارية',
                                'clarification' => 'للتوضيح',
                                'follow_up' => 'متابعة',
                            ])
                            ->default('optional')
                            ->native(false)
                            ->required(),
                            
                        Forms\Components\Toggle::make('attended')
                            ->label('تم الحضور')
                            ->inline(false)
                            ->default(false),
                    ]),
                    
                Forms\Components\Section::make('الموقع')
                    ->icon('heroicon-o-map')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('location')
                            ->label('عنوان الموقع')
                            ->maxLength(255)
                            ->placeholder('مثال: شارع المطار، عمان'),
                            
                        Forms\Components\TextInput::make('coordinates')
                            ->label('الإحداثيات')
                            ->placeholder('31.9539, 35.9106')
                            ->maxLength(100),
                            
                        Forms\Components\Textarea::make('meeting_point')
                            ->label('نقطة التجمع')
                            ->rows(2)
                            ->columnSpanFull()
                            ->placeholder('مكان التجمع والتعليمات'),
                    ]),
                    
                Forms\Components\Section::make('الفريق والتواصل')
                    ->icon('heroicon-o-users')
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        Forms\Components\Select::make('team_members')
                            ->label('أعضاء الفريق')
                            ->relationship('teamMembers', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->placeholder('اختر أعضاء الفريق'),
                            
                        Forms\Components\TextInput::make('contact_person')
                            ->label('شخص التواصل')
                            ->maxLength(255),
                            
                        Forms\Components\TextInput::make('contact_phone')
                            ->label('رقم الهاتف')
                            ->tel()
                            ->maxLength(20),
                            
                        Forms\Components\TextInput::make('contact_email')
                            ->label('البريد الإلكتروني')
                            ->email()
                            ->maxLength(255),
                    ]),
                    
                Forms\Components\Section::make('الملاحظات والنتائج')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات قبل الزيارة')
                            ->rows(2)
                            ->columnSpanFull()
                            ->placeholder('تعليمات خاصة، أدوات مطلوبة، إلخ'),
                            
                        Forms\Components\Textarea::make('findings')
                            ->label('النتائج والملاحظات الميدانية')
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('ما تم ملاحظته في الموقع'),
                            
                        Forms\Components\Textarea::make('concerns')
                            ->label('مخاوف أو تحديات')
                            ->rows(2)
                            ->columnSpanFull()
                            ->placeholder('أي تحديات أو مخاوف تم اكتشافها'),
                    ]),
                    
                Forms\Components\Section::make('الصور والملفات')
                    ->icon('heroicon-o-photo')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\FileUpload::make('photos')
                            ->label('صور الموقع')
                            ->multiple()
                            ->image()
                            ->directory('site-visits')
                            ->maxFiles(10)
                            ->columnSpanFull(),
                            
                        Forms\Components\FileUpload::make('report_file')
                            ->label('تقرير الزيارة')
                            ->directory('site-visit-reports')
                            ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('visit_date')
            ->defaultSort('visit_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('visit_date')
                    ->label('التاريخ والوقت')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->description(fn ($record) => Carbon::parse($record->visit_date)->diffForHumans())
                    ->color(fn ($record) => 
                        Carbon::parse($record->visit_date)->isFuture() 
                            ? 'warning' 
                            : ($record->attended ? 'success' : 'danger')
                    ),
                    
                Tables\Columns\TextColumn::make('visit_type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'mandatory' => 'إلزامية',
                        'optional' => 'اختيارية',
                        'clarification' => 'توضيح',
                        'follow_up' => 'متابعة',
                        default => $state,
                    })
                    ->color(fn ($state) => match($state) {
                        'mandatory' => 'danger',
                        'optional' => 'gray',
                        'clarification' => 'info',
                        'follow_up' => 'warning',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('location')
                    ->label('الموقع')
                    ->limit(30)
                    ->searchable()
                    ->tooltip(fn ($state) => $state),
                    
                Tables\Columns\IconColumn::make('attended')
                    ->label('تم الحضور')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                    
                Tables\Columns\TextColumn::make('findings')
                    ->label('النتائج')
                    ->limit(40)
                    ->toggleable()
                    ->tooltip(fn ($state) => $state),
                    
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->getStateUsing(fn ($record) => 
                        Carbon::parse($record->visit_date)->isFuture() 
                            ? 'upcoming' 
                            : ($record->attended ? 'attended' : 'missed')
                    )
                    ->formatStateUsing(fn ($state) => match($state) {
                        'upcoming' => 'قادمة',
                        'attended' => 'تم الحضور',
                        'missed' => 'لم يتم الحضور',
                    })
                    ->color(fn ($state) => match($state) {
                        'upcoming' => 'warning',
                        'attended' => 'success',
                        'missed' => 'danger',
                    })
                    ->icon(fn ($state) => match($state) {
                        'upcoming' => 'heroicon-o-clock',
                        'attended' => 'heroicon-o-check',
                        'missed' => 'heroicon-o-x-mark',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('visit_type')
                    ->label('نوع الزيارة')
                    ->options([
                        'mandatory' => 'إلزامية',
                        'optional' => 'اختيارية',
                        'clarification' => 'توضيح',
                        'follow_up' => 'متابعة',
                    ]),
                    
                Tables\Filters\TernaryFilter::make('attended')
                    ->label('تم الحضور')
                    ->placeholder('الكل')
                    ->trueLabel('تم الحضور')
                    ->falseLabel('لم يتم الحضور'),
                    
                Tables\Filters\Filter::make('upcoming')
                    ->label('الزيارات القادمة')
                    ->query(fn ($query) => $query->where('visit_date', '>', now()))
                    ->toggle(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->icon('heroicon-o-plus')
                    ->label('إضافة زيارة'),
                    
                Tables\Actions\Action::make('add_mandatory')
                    ->label('إضافة زيارة إلزامية')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('danger')
                    ->form([
                        Forms\Components\DateTimePicker::make('visit_date')
                            ->label('تاريخ ووقت الزيارة')
                            ->required()
                            ->native(false),
                        Forms\Components\TextInput::make('location')
                            ->label('الموقع')
                            ->required(),
                        Forms\Components\Textarea::make('notes')
                            ->label('تعليمات')
                            ->rows(2),
                    ])
                    ->action(function (array $data) {
                        $tender = $this->getOwnerRecord();
                        $tender->siteVisits()->create([
                            ...$data,
                            'visit_type' => 'mandatory',
                        ]);
                        
                        Notification::make()
                            ->title('تمت إضافة الزيارة الإلزامية')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    
                    Tables\Actions\Action::make('mark_attended')
                        ->label('تسجيل الحضور')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->visible(fn ($record) => !$record->attended && Carbon::parse($record->visit_date)->isPast())
                        ->form([
                            Forms\Components\Textarea::make('findings')
                                ->label('النتائج والملاحظات')
                                ->rows(3)
                                ->required(),
                        ])
                        ->action(function ($record, array $data) {
                            $record->update([
                                'attended' => true,
                                'findings' => $data['findings'],
                            ]);
                            
                            Notification::make()
                                ->title('تم تسجيل الحضور')
                                ->success()
                                ->send();
                        }),
                        
                    Tables\Actions\Action::make('add_reminder')
                        ->label('تذكير')
                        ->icon('heroicon-o-bell')
                        ->color('warning')
                        ->visible(fn ($record) => Carbon::parse($record->visit_date)->isFuture())
                        ->action(function ($record) {
                            Notification::make()
                                ->title('سيتم إضافة تذكير')
                                ->info()
                                ->send();
                        }),
                        
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('mark_all_attended')
                        ->label('تسجيل حضور الكل')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each->update(['attended' => true]);
                            Notification::make()->title('تم تسجيل الحضور')->success()->send();
                        }),
                ]),
            ])
            ->emptyStateHeading('لا توجد زيارات')
            ->emptyStateDescription('أضف زيارات الموقع للعطاء')
            ->emptyStateIcon('heroicon-o-map-pin')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('إضافة زيارة جديدة')
                    ->icon('heroicon-o-plus'),
            ]);
    }
}
