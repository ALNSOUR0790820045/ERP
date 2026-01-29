<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenderAwardDecisionResource\Pages;
use App\Models\TenderAwardDecision;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TenderAwardDecisionResource extends Resource
{
    protected static ?string $model = TenderAwardDecision::class;

    protected static ?string $navigationIcon = 'heroicon-o-trophy';
    
    protected static ?string $navigationGroup = 'العطاءات والمناقصات';
    
    protected static ?string $navigationLabel = 'قرارات الإحالة';
    
    protected static ?string $modelLabel = 'قرار إحالة';
    
    protected static ?string $pluralModelLabel = 'قرارات الإحالة';
    
    protected static ?int $navigationSort = 16;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('بيانات القرار')
                    ->schema([
                        Forms\Components\Select::make('tender_id')
                            ->relationship('tender', 'name_ar')
                            ->label('العطاء')
                            ->required()
                            ->searchable(),
                        Forms\Components\Select::make('decision_type')
                            ->label('نوع القرار')
                            ->options(TenderAwardDecision::DECISION_TYPES)
                            ->required(),
                        Forms\Components\DatePicker::make('decision_date')
                            ->label('تاريخ القرار')
                            ->required()
                            ->default(now()),
                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options(TenderAwardDecision::STATUSES)
                            ->default('preliminary'),
                    ])->columns(2),
                    
                Forms\Components\Section::make('الفائز')
                    ->schema([
                        Forms\Components\Select::make('winner_competitor_id')
                            ->relationship('winner', 'company_name')
                            ->label('الشركة الفائزة')
                            ->searchable(),
                        Forms\Components\TextInput::make('award_amount')
                            ->label('مبلغ الإحالة')
                            ->numeric(),
                        Forms\Components\Select::make('currency_id')
                            ->relationship('currency', 'code')
                            ->label('العملة'),
                    ])->columns(3),
                    
                Forms\Components\Section::make('المبررات')
                    ->schema([
                        Forms\Components\Textarea::make('justification_ar')
                            ->label('مبررات القرار (عربي)')
                            ->required()
                            ->rows(4),
                        Forms\Components\Textarea::make('justification_en')
                            ->label('مبررات القرار (إنجليزي)')
                            ->rows(4),
                    ])->columns(2),
                    
                Forms\Components\Section::make('فترة التوقف (الاعتراض)')
                    ->description('حسب المادة 37 من تعليمات المناقصين')
                    ->schema([
                        Forms\Components\DatePicker::make('preliminary_announcement_date')
                            ->label('تاريخ الإعلان المبدئي'),
                        Forms\Components\TextInput::make('standstill_period_days')
                            ->label('فترة التوقف (يوم)')
                            ->numeric()
                            ->default(7),
                        Forms\Components\DatePicker::make('standstill_end_date')
                            ->label('نهاية فترة التوقف'),
                        Forms\Components\Toggle::make('objections_received')
                            ->label('هل وردت اعتراضات'),
                    ])->columns(2),
                    
                Forms\Components\Section::make('القرار النهائي')
                    ->schema([
                        Forms\Components\DatePicker::make('final_decision_date')
                            ->label('تاريخ القرار النهائي'),
                        Forms\Components\Textarea::make('final_decision_notes')
                            ->label('ملاحظات القرار النهائي')
                            ->rows(2),
                    ])->columns(2),
                    
                Forms\Components\Section::make('خطاب الإحالة')
                    ->schema([
                        Forms\Components\TextInput::make('award_letter_number')
                            ->label('رقم خطاب الإحالة')
                            ->maxLength(100),
                        Forms\Components\DatePicker::make('award_letter_date')
                            ->label('تاريخ خطاب الإحالة'),
                        Forms\Components\FileUpload::make('award_letter_path')
                            ->label('ملف خطاب الإحالة')
                            ->directory('award-decisions'),
                    ])->columns(3),
                    
                Forms\Components\Section::make('اللجنة')
                    ->schema([
                        Forms\Components\TextInput::make('committee_name')
                            ->label('اسم اللجنة')
                            ->maxLength(255),
                        Forms\Components\KeyValue::make('committee_members')
                            ->label('أعضاء اللجنة')
                            ->keyLabel('الاسم')
                            ->valueLabel('المنصب'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tender.name_ar')
                    ->label('العطاء')
                    ->searchable()
                    ->limit(25),
                Tables\Columns\TextColumn::make('decision_type')
                    ->label('نوع القرار')
                    ->badge()
                    ->formatStateUsing(fn ($state) => TenderAwardDecision::DECISION_TYPES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'award_to_winner' => 'success',
                        'cancel_tender' => 'danger',
                        'rebid' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('winner.company_name')
                    ->label('الفائز')
                    ->limit(20),
                Tables\Columns\TextColumn::make('award_amount')
                    ->label('المبلغ')
                    ->money(fn ($record) => $record->currency?->code ?? 'JOD'),
                Tables\Columns\TextColumn::make('decision_date')
                    ->label('تاريخ القرار')
                    ->date()
                    ->sortable(),
                Tables\Columns\IconColumn::make('objections_received')
                    ->label('اعتراضات')
                    ->boolean(),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn ($state) => TenderAwardDecision::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'final' => 'success',
                        'preliminary' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('decision_type')
                    ->label('نوع القرار')
                    ->options(TenderAwardDecision::DECISION_TYPES),
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(TenderAwardDecision::STATUSES),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListTenderAwardDecisions::route('/'),
            'create' => Pages\CreateTenderAwardDecision::route('/create'),
            'edit' => Pages\EditTenderAwardDecision::route('/{record}/edit'),
        ];
    }
}
