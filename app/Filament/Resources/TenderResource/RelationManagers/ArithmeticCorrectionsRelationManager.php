<?php

namespace App\Filament\Resources\TenderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Tenders\TenderArithmeticCorrection;

class ArithmeticCorrectionsRelationManager extends RelationManager
{
    protected static string $relationship = 'arithmeticCorrections';

    protected static ?string $recordTitleAttribute = 'item_number';
    
    protected static ?string $title = 'التصحيحات الحسابية';
    
    protected static ?string $modelLabel = 'تصحيح حسابي';
    
    protected static ?string $pluralModelLabel = 'التصحيحات الحسابية';

    public function form(Form $form): Form
    {
        $correctionTypes = collect(TenderArithmeticCorrection::getCorrectionTypes())
            ->mapWithKeys(fn ($info, $key) => [$key => $info['label']]);
            
        return $form
            ->schema([
                Forms\Components\Section::make('بيانات المناقص')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('bidder_name')
                            ->label('اسم المناقص')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('bidder_id')
                            ->label('المناقص (من النظام)')
                            ->relationship('bidder', 'name')
                            ->searchable()
                            ->preload(),
                    ]),
                    
                Forms\Components\Section::make('تفاصيل التصحيح')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('correction_type')
                            ->label('نوع التصحيح')
                            ->options($correctionTypes)
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $types = TenderArithmeticCorrection::getCorrectionTypes();
                                if (isset($types[$state])) {
                                    $set('correction_rule', $types[$state]['rule']);
                                }
                            }),
                        Forms\Components\TextInput::make('item_number')
                            ->label('رقم البند')
                            ->maxLength(50),
                        Forms\Components\TextInput::make('item_description')
                            ->label('وصف البند')
                            ->maxLength(500)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('correction_rule')
                            ->label('القاعدة المطبقة')
                            ->rows(2)
                            ->disabled()
                            ->columnSpanFull(),
                    ]),
                    
                Forms\Components\Section::make('القيم')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('original_value')
                            ->label('القيمة الأصلية')
                            ->numeric()
                            ->prefix('د.أ')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                $corrected = $get('corrected_value');
                                if ($state && $corrected) {
                                    $set('difference', $corrected - $state);
                                }
                            }),
                        Forms\Components\TextInput::make('corrected_value')
                            ->label('القيمة المصححة')
                            ->numeric()
                            ->prefix('د.أ')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                $original = $get('original_value');
                                if ($state && $original) {
                                    $set('difference', $state - $original);
                                }
                            }),
                        Forms\Components\TextInput::make('difference')
                            ->label('الفرق')
                            ->numeric()
                            ->prefix('د.أ')
                            ->disabled(),
                    ]),
                    
                Forms\Components\Section::make('السند')
                    ->schema([
                        Forms\Components\Textarea::make('correction_basis')
                            ->label('أساس التصحيح')
                            ->rows(3)
                            ->helperText('توضيح كيفية الوصول للقيمة المصححة'),
                    ]),
                    
                Forms\Components\Section::make('قبول المناقص')
                    ->columns(3)
                    ->schema([
                        Forms\Components\DatePicker::make('notification_date')
                            ->label('تاريخ الإخطار')
                            ->default(now()),
                        Forms\Components\DatePicker::make('response_date')
                            ->label('تاريخ الرد'),
                        Forms\Components\ToggleButtons::make('bidder_accepted')
                            ->label('قبول المناقص')
                            ->boolean()
                            ->inline()
                            ->options([
                                true => 'مقبول',
                                false => 'مرفوض',
                            ])
                            ->colors([
                                true => 'success',
                                false => 'danger',
                            ]),
                        Forms\Components\Toggle::make('bid_rejected_for_refusal')
                            ->label('تم رفض العرض لرفض التصحيح')
                            ->helperText('عند الرفض: يتم مصادرة ضمان العطاء'),
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        $correctionTypes = collect(TenderArithmeticCorrection::getCorrectionTypes())
            ->mapWithKeys(fn ($info, $key) => [$key => $info['label']]);
            
        return $table
            ->recordTitleAttribute('item_number')
            ->columns([
                Tables\Columns\TextColumn::make('bidder_name')
                    ->label('المناقص')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('correction_type')
                    ->label('نوع التصحيح')
                    ->formatStateUsing(fn ($state) => $correctionTypes[$state] ?? $state)
                    ->wrap()
                    ->limit(30),
                Tables\Columns\TextColumn::make('item_number')
                    ->label('رقم البند')
                    ->searchable(),
                Tables\Columns\TextColumn::make('original_value')
                    ->label('القيمة الأصلية')
                    ->money('JOD')
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('corrected_value')
                    ->label('القيمة المصححة')
                    ->money('JOD')
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('difference')
                    ->label('الفرق')
                    ->money('JOD')
                    ->alignEnd()
                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('bidder_accepted')
                    ->label('القبول')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        true => 'مقبول',
                        false => 'مرفوض',
                        null => 'في الانتظار',
                    })
                    ->color(fn ($state) => match ($state) {
                        true => 'success',
                        false => 'danger',
                        null => 'warning',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('correction_type')
                    ->label('النوع')
                    ->options($correctionTypes),
                Tables\Filters\TernaryFilter::make('bidder_accepted')
                    ->label('القبول')
                    ->placeholder('الكل')
                    ->trueLabel('مقبول')
                    ->falseLabel('مرفوض'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('recordAcceptance')
                    ->label('تسجيل القبول')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(fn ($record) => $record->recordAcceptance())
                    ->visible(fn ($record) => $record->bidder_accepted === null)
                    ->requiresConfirmation(),
                Tables\Actions\Action::make('recordRejection')
                    ->label('تسجيل الرفض')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->action(fn ($record) => $record->recordRejection())
                    ->visible(fn ($record) => $record->bidder_accepted === null)
                    ->requiresConfirmation()
                    ->modalDescription('سيتم مصادرة ضمان العطاء للمناقص'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
