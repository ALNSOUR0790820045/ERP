<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenderAwardTrackingResource\Pages;
use App\Models\Tenders\TenderAwardTracking;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TenderAwardTrackingResource extends Resource
{
    protected static ?string $model = TenderAwardTracking::class;

    protected static ?string $navigationIcon = 'heroicon-o-trophy';
    protected static ?string $navigationGroup = 'العطاءات والمناقصات';
    protected static ?string $navigationLabel = 'متابعة الإحالة';
    protected static ?string $modelLabel = 'متابعة إحالة';
    protected static ?string $pluralModelLabel = 'متابعة الإحالات';
    protected static ?int $navigationSort = 17;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('معلومات العطاء والإحالة')->schema([
                Forms\Components\Select::make('tender_id')
                    ->label('العطاء')
                    ->relationship('tender', 'name_ar')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label('حالة الإحالة')
                    ->options(TenderAwardTracking::STATUSES)
                    ->default('pending_announcement')
                    ->required(),
                Forms\Components\DatePicker::make('expected_announcement_date')
                    ->label('تاريخ الإعلان المتوقع'),
                Forms\Components\DatePicker::make('actual_announcement_date')
                    ->label('تاريخ الإعلان الفعلي'),
            ])->columns(2),

            Forms\Components\Section::make('نتيجة الإحالة')->schema([
                Forms\Components\Toggle::make('is_awarded_to_us')
                    ->label('أحيل إلينا')
                    ->reactive(),
                Forms\Components\TextInput::make('competitor_name')
                    ->label('اسم المنافس الفائز')
                    ->maxLength(255)
                    ->visible(fn($get) => !$get('is_awarded_to_us')),
                Forms\Components\TextInput::make('competitor_price')
                    ->label('سعر المنافس')
                    ->numeric()
                    ->prefix('JOD')
                    ->visible(fn($get) => !$get('is_awarded_to_us')),
                Forms\Components\TextInput::make('our_rank')
                    ->label('ترتيبنا')
                    ->numeric()
                    ->minValue(1),
            ])->columns(2),

            Forms\Components\Section::make('الاعتراض والتظلم')->schema([
                Forms\Components\Toggle::make('can_appeal')
                    ->label('يمكن التظلم')
                    ->reactive(),
                Forms\Components\DatePicker::make('appeal_deadline')
                    ->label('آخر موعد للتظلم')
                    ->visible(fn($get) => $get('can_appeal')),
                Forms\Components\Toggle::make('appeal_submitted')
                    ->label('تم تقديم تظلم')
                    ->visible(fn($get) => $get('can_appeal')),
                Forms\Components\Textarea::make('appeal_notes')
                    ->label('ملاحظات التظلم')
                    ->rows(3)
                    ->visible(fn($get) => $get('can_appeal'))
                    ->columnSpanFull(),
            ])->columns(2),

            Forms\Components\Section::make('معلومات إضافية')->schema([
                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات')
                    ->rows(4)
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tender.name_ar')
                    ->label('العطاء')
                    ->limit(30)
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn($state) => TenderAwardTracking::STATUSES[$state] ?? $state)
                    ->color(fn($state) => match($state) {
                        'awarded_to_us' => 'success',
                        'awarded_to_competitor' => 'danger',
                        'pending_announcement' => 'warning',
                        'cancelled' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('is_awarded_to_us')
                    ->label('فزنا')
                    ->boolean(),
                Tables\Columns\TextColumn::make('our_rank')
                    ->label('ترتيبنا')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('expected_announcement_date')
                    ->label('الإعلان المتوقع')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('actual_announcement_date')
                    ->label('الإعلان الفعلي')
                    ->date()
                    ->sortable(),
                Tables\Columns\IconColumn::make('appeal_submitted')
                    ->label('تظلم')
                    ->boolean(),
            ])
            ->defaultSort('expected_announcement_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(TenderAwardTracking::STATUSES),
                Tables\Filters\TernaryFilter::make('is_awarded_to_us')
                    ->label('فزنا'),
            ])
            ->actions([
                Tables\Actions\Action::make('markAwarded')
                    ->label('تأكيد الفوز')
                    ->icon('heroicon-o-trophy')
                    ->color('success')
                    ->visible(fn($record) => $record->status === 'pending_announcement')
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'awarded_to_us',
                            'is_awarded_to_us' => true,
                            'actual_announcement_date' => now(),
                        ]);
                    }),
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
            'index' => Pages\ListTenderAwardTrackings::route('/'),
            'create' => Pages\CreateTenderAwardTracking::route('/create'),
            'edit' => Pages\EditTenderAwardTracking::route('/{record}/edit'),
        ];
    }
}
