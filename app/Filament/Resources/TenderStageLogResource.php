<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenderStageLogResource\Pages;
use App\Models\Tenders\TenderStageLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TenderStageLogResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = TenderStageLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';
    protected static ?string $navigationGroup = 'العطاءات والمناقصات';
    protected static ?string $navigationLabel = 'سجل المراحل';
    protected static ?string $modelLabel = 'مرحلة';
    protected static ?string $pluralModelLabel = 'سجل المراحل';
    protected static ?int $navigationSort = 13;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('بيانات المرحلة')->schema([
                Forms\Components\Select::make('tender_id')
                    ->label('العطاء')
                    ->relationship('tender', 'name_ar')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('stage')
                    ->label('المرحلة')
                    ->options(collect(TenderStageLog::STAGES)->mapWithKeys(fn($v, $k) => [$k => $v['label']]))
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label('الحالة')
                    ->options(TenderStageLog::STATUSES)
                    ->default('not_started')
                    ->required(),
                Forms\Components\TextInput::make('stage_order')
                    ->label('الترتيب')
                    ->numeric()
                    ->required(),
            ])->columns(2),

            Forms\Components\Section::make('التوقيت')->schema([
                Forms\Components\DateTimePicker::make('started_at')
                    ->label('وقت البدء'),
                Forms\Components\DateTimePicker::make('completed_at')
                    ->label('وقت الاكتمال'),
                Forms\Components\Toggle::make('is_mandatory')
                    ->label('مرحلة إلزامية')
                    ->default(true),
            ])->columns(3),

            Forms\Components\Textarea::make('notes')
                ->label('ملاحظات')
                ->rows(3)
                ->columnSpanFull(),
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
                Tables\Columns\TextColumn::make('stage_order')
                    ->label('#')
                    ->sortable(),
                Tables\Columns\TextColumn::make('stage')
                    ->label('المرحلة')
                    ->formatStateUsing(fn($state) => TenderStageLog::STAGES[$state]['label'] ?? $state),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn($state) => TenderStageLog::STATUSES[$state] ?? $state)
                    ->color(fn($state) => match($state) {
                        'completed' => 'success',
                        'in_progress' => 'warning',
                        'not_started' => 'gray',
                        'skipped' => 'info',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('started_at')
                    ->label('البدء')
                    ->dateTime('Y-m-d H:i'),
                Tables\Columns\TextColumn::make('completed_at')
                    ->label('الاكتمال')
                    ->dateTime('Y-m-d H:i'),
                Tables\Columns\IconColumn::make('is_mandatory')
                    ->label('إلزامي')
                    ->boolean(),
            ])
            ->defaultSort('stage_order', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(TenderStageLog::STATUSES),
                Tables\Filters\TernaryFilter::make('is_mandatory')
                    ->label('إلزامي'),
            ])
            ->actions([
                Tables\Actions\Action::make('start')
                    ->label('بدء')
                    ->icon('heroicon-o-play')
                    ->color('warning')
                    ->visible(fn($record) => $record->status === 'not_started')
                    ->action(fn($record) => $record->start()),
                Tables\Actions\Action::make('complete')
                    ->label('إكمال')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn($record) => $record->status === 'in_progress')
                    ->action(fn($record) => $record->complete(auth()->user())),
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
            'index' => Pages\ListTenderStageLogs::route('/'),
            'create' => Pages\CreateTenderStageLog::route('/create'),
            'edit' => Pages\EditTenderStageLog::route('/{record}/edit'),
        ];
    }
}
