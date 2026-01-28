<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationTemplateResource\Pages;
use App\Models\NotificationTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class NotificationTemplateResource extends Resource
{
    protected static ?string $model = NotificationTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?string $navigationGroup = 'الإعدادات';

    protected static ?string $navigationLabel = 'قوالب الإشعارات';

    protected static ?string $modelLabel = 'قالب إشعار';

    protected static ?string $pluralModelLabel = 'قوالب الإشعارات';

    protected static ?int $navigationSort = 85;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات القالب')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('الرمز')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50),

                        Forms\Components\TextInput::make('name')
                            ->label('الاسم')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('category')
                            ->label('الفئة')
                            ->options(NotificationTemplate::CATEGORIES)
                            ->required(),

                        Forms\Components\Select::make('event_type')
                            ->label('نوع الحدث')
                            ->options(NotificationTemplate::EVENT_TYPES)
                            ->required(),

                        Forms\Components\Select::make('priority')
                            ->label('الأولوية')
                            ->options(NotificationTemplate::PRIORITIES)
                            ->default('normal')
                            ->required(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('نشط')
                            ->default(true),
                    ])->columns(3),

                Forms\Components\Section::make('محتوى الإشعار الداخلي')
                    ->schema([
                        Forms\Components\TextInput::make('title_template')
                            ->label('عنوان الإشعار')
                            ->required()
                            ->maxLength(255)
                            ->helperText('يمكنك استخدام {{variable}} للمتغيرات'),

                        Forms\Components\Textarea::make('body_template')
                            ->label('نص الإشعار')
                            ->required()
                            ->rows(3)
                            ->helperText('يمكنك استخدام {{variable}} للمتغيرات'),
                    ]),

                Forms\Components\Section::make('محتوى البريد الإلكتروني')
                    ->schema([
                        Forms\Components\TextInput::make('email_subject')
                            ->label('عنوان البريد')
                            ->maxLength(255),

                        Forms\Components\RichEditor::make('email_body')
                            ->label('نص البريد')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make('محتوى الرسالة النصية')
                    ->schema([
                        Forms\Components\Textarea::make('sms_body')
                            ->label('نص الرسالة')
                            ->maxLength(160)
                            ->helperText('الحد الأقصى 160 حرف'),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make('محتوى الإشعار الفوري')
                    ->schema([
                        Forms\Components\TextInput::make('push_title')
                            ->label('عنوان الإشعار')
                            ->maxLength(100),

                        Forms\Components\Textarea::make('push_body')
                            ->label('نص الإشعار')
                            ->maxLength(255),
                    ])->columns(2)
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make('إعدادات القنوات')
                    ->schema([
                        Forms\Components\CheckboxList::make('channels')
                            ->label('القنوات المفعلة')
                            ->options(NotificationTemplate::CHANNELS)
                            ->default(['database'])
                            ->columns(3),

                        Forms\Components\TagsInput::make('variables')
                            ->label('المتغيرات المتاحة')
                            ->helperText('أسماء المتغيرات التي يمكن استخدامها في القوالب'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('الرمز')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('category')
                    ->label('الفئة')
                    ->formatStateUsing(fn ($state) => NotificationTemplate::CATEGORIES[$state] ?? $state)
                    ->colors([
                        'primary' => 'system',
                        'success' => 'hr',
                        'warning' => 'finance',
                        'info' => 'project',
                    ]),

                Tables\Columns\TextColumn::make('event_type')
                    ->label('الحدث')
                    ->formatStateUsing(fn ($state) => NotificationTemplate::EVENT_TYPES[$state] ?? $state)
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('priority')
                    ->label('الأولوية')
                    ->formatStateUsing(fn ($state) => NotificationTemplate::PRIORITIES[$state] ?? $state)
                    ->colors([
                        'gray' => 'low',
                        'info' => 'normal',
                        'warning' => 'high',
                        'danger' => 'urgent',
                    ]),

                Tables\Columns\TextColumn::make('channels')
                    ->label('القنوات')
                    ->formatStateUsing(function ($state) {
                        if (!is_array($state)) return '';
                        return collect($state)->map(fn ($c) => NotificationTemplate::CHANNELS[$c] ?? $c)->join(', ');
                    })
                    ->wrap()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),

                Tables\Columns\TextColumn::make('logs_count')
                    ->label('عدد الإرسالات')
                    ->counts('logs')
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('آخر تحديث')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('الفئة')
                    ->options(NotificationTemplate::CATEGORIES),

                Tables\Filters\SelectFilter::make('event_type')
                    ->label('نوع الحدث')
                    ->options(NotificationTemplate::EVENT_TYPES),

                Tables\Filters\SelectFilter::make('priority')
                    ->label('الأولوية')
                    ->options(NotificationTemplate::PRIORITIES),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('نشط'),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('test')
                    ->label('اختبار')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->requiresConfirmation()
                    ->action(function (NotificationTemplate $record) {
                        $record->send(auth()->user(), ['test' => 'قيمة اختبارية']);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
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
            'index' => Pages\ListNotificationTemplates::route('/'),
            'create' => Pages\CreateNotificationTemplate::route('/create'),
            'view' => Pages\ViewNotificationTemplate::route('/{record}'),
            'edit' => Pages\EditNotificationTemplate::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
