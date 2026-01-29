<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenderAlertResource\Pages;
use App\Models\Tenders\TenderAlert;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TenderAlertResource extends Resource
{
    protected static ?string $model = TenderAlert::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';
    protected static ?string $navigationGroup = 'العطاءات والمناقصات';
    protected static ?string $navigationLabel = 'التنبيهات';
    protected static ?string $modelLabel = 'تنبيه';
    protected static ?string $pluralModelLabel = 'التنبيهات';
    protected static ?int $navigationSort = 12;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('بيانات التنبيه')->schema([
                Forms\Components\Select::make('tender_id')
                    ->label('العطاء')
                    ->relationship('tender', 'name_ar')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('alert_type')
                    ->label('نوع التنبيه')
                    ->options(TenderAlert::ALERT_TYPES)
                    ->required(),
                Forms\Components\Select::make('priority')
                    ->label('الأولوية')
                    ->options(TenderAlert::PRIORITIES)
                    ->default('medium')
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label('الحالة')
                    ->options(TenderAlert::STATUSES)
                    ->default('pending')
                    ->required(),
            ])->columns(2),

            Forms\Components\Section::make('المحتوى')->schema([
                Forms\Components\TextInput::make('title_ar')
                    ->label('العنوان بالعربية')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('title_en')
                    ->label('العنوان بالإنجليزية')
                    ->maxLength(255),
                Forms\Components\Textarea::make('message_ar')
                    ->label('الرسالة بالعربية')
                    ->rows(3),
                Forms\Components\Textarea::make('message_en')
                    ->label('الرسالة بالإنجليزية')
                    ->rows(3),
            ])->columns(2),

            Forms\Components\Section::make('التوقيت')->schema([
                Forms\Components\DateTimePicker::make('alert_date')
                    ->label('تاريخ التنبيه')
                    ->required(),
                Forms\Components\DateTimePicker::make('due_date')
                    ->label('تاريخ الاستحقاق'),
                Forms\Components\TextInput::make('days_before')
                    ->label('أيام قبل')
                    ->numeric(),
            ])->columns(3),

            Forms\Components\Section::make('طرق الإرسال')->schema([
                Forms\Components\Toggle::make('system_notification')
                    ->label('إشعار نظام')
                    ->default(true),
                Forms\Components\Toggle::make('email_sent')
                    ->label('بريد إلكتروني')
                    ->default(false),
                Forms\Components\Toggle::make('sms_sent')
                    ->label('رسالة SMS')
                    ->default(false),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tender.name_ar')
                    ->label('العطاء')
                    ->limit(25)
                    ->searchable(),
                Tables\Columns\TextColumn::make('title_ar')
                    ->label('العنوان')
                    ->limit(30)
                    ->searchable(),
                Tables\Columns\TextColumn::make('alert_type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn($state) => TenderAlert::ALERT_TYPES[$state] ?? $state),
                Tables\Columns\TextColumn::make('priority')
                    ->label('الأولوية')
                    ->badge()
                    ->formatStateUsing(fn($state) => TenderAlert::PRIORITIES[$state] ?? $state)
                    ->color(fn($state) => match($state) {
                        'urgent' => 'danger',
                        'high' => 'warning',
                        'medium' => 'info',
                        'low' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('الاستحقاق')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn($state) => TenderAlert::STATUSES[$state] ?? $state)
                    ->color(fn($state) => match($state) {
                        'read' => 'success',
                        'sent' => 'info',
                        'pending' => 'warning',
                        'dismissed' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('due_date', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(TenderAlert::STATUSES),
                Tables\Filters\SelectFilter::make('priority')
                    ->label('الأولوية')
                    ->options(TenderAlert::PRIORITIES),
                Tables\Filters\SelectFilter::make('alert_type')
                    ->label('النوع')
                    ->options(TenderAlert::ALERT_TYPES),
            ])
            ->actions([
                Tables\Actions\Action::make('markRead')
                    ->label('قراءة')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn($record) => in_array($record->status, ['pending', 'sent']))
                    ->action(fn($record) => $record->markAsRead()),
                Tables\Actions\Action::make('dismiss')
                    ->label('رفض')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn($record) => $record->status !== 'dismissed')
                    ->action(fn($record) => $record->dismiss()),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenderAlerts::route('/'),
            'create' => Pages\CreateTenderAlert::route('/create'),
            'edit' => Pages\EditTenderAlert::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
}
