<?php

namespace App\Filament\Resources\TenderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Tenders\TenderPricePreference;

class PricePreferencesRelationManager extends RelationManager
{
    protected static string $relationship = 'pricePreferences';

    protected static ?string $recordTitleAttribute = 'preference_type';
    
    protected static ?string $title = 'الأفضليات السعرية';
    
    protected static ?string $modelLabel = 'أفضلية سعرية';
    
    protected static ?string $pluralModelLabel = 'الأفضليات السعرية';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('بيانات الأفضلية')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('preference_type')
                            ->label('نوع الأفضلية')
                            ->options(TenderPricePreference::getPreferenceTypes())
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $percentage = TenderPricePreference::getDefaultPercentage($state);
                                if ($percentage) {
                                    $set('preference_percentage', $percentage);
                                }
                            }),
                        Forms\Components\TextInput::make('preference_percentage')
                            ->label('نسبة الأفضلية (%)')
                            ->numeric()
                            ->suffix('%')
                            ->required(),
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
                    
                Forms\Components\Section::make('التحقق من الأهلية')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Toggle::make('is_verified')
                            ->label('تم التحقق')
                            ->live(),
                        Forms\Components\DatePicker::make('verification_date')
                            ->label('تاريخ التحقق')
                            ->visible(fn (Forms\Get $get) => $get('is_verified')),
                        Forms\Components\Textarea::make('verification_documents')
                            ->label('المستندات الداعمة')
                            ->rows(2)
                            ->columnSpanFull()
                            ->helperText('شهادة SME، سجل تجاري، إلخ.'),
                        Forms\Components\Textarea::make('verification_notes')
                            ->label('ملاحظات التحقق')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
                    
                Forms\Components\Section::make('الحسابات')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('original_price')
                            ->label('السعر الأصلي')
                            ->numeric()
                            ->prefix('د.أ')
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                $percentage = $get('preference_percentage');
                                if ($state && $percentage) {
                                    $adjustment = $state * ($percentage / 100);
                                    $set('adjustment_amount', $adjustment);
                                    $set('adjusted_price', $state - $adjustment);
                                }
                            }),
                        Forms\Components\TextInput::make('adjustment_amount')
                            ->label('مبلغ التعديل')
                            ->numeric()
                            ->prefix('د.أ')
                            ->disabled(),
                        Forms\Components\TextInput::make('adjusted_price')
                            ->label('السعر بعد التعديل')
                            ->numeric()
                            ->prefix('د.أ')
                            ->disabled()
                            ->helperText('للمقارنة فقط'),
                    ]),
                    
                Forms\Components\Section::make('الاعتماد')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        Forms\Components\Toggle::make('is_approved')
                            ->label('معتمد')
                            ->live(),
                        Forms\Components\DatePicker::make('approved_date')
                            ->label('تاريخ الاعتماد')
                            ->visible(fn (Forms\Get $get) => $get('is_approved')),
                        Forms\Components\Textarea::make('approval_notes')
                            ->label('ملاحظات الاعتماد')
                            ->rows(2)
                            ->columnSpanFull()
                            ->visible(fn (Forms\Get $get) => $get('is_approved')),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('preference_type')
            ->columns([
                Tables\Columns\TextColumn::make('preference_type')
                    ->label('نوع الأفضلية')
                    ->formatStateUsing(fn ($state) => TenderPricePreference::getPreferenceTypes()[$state] ?? $state)
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('bidder_name')
                    ->label('المناقص')
                    ->searchable(),
                Tables\Columns\TextColumn::make('preference_percentage')
                    ->label('النسبة')
                    ->suffix('%')
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('original_price')
                    ->label('السعر الأصلي')
                    ->money('JOD')
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('adjustment_amount')
                    ->label('التعديل')
                    ->money('JOD')
                    ->alignEnd()
                    ->color('success'),
                Tables\Columns\IconColumn::make('is_verified')
                    ->label('تم التحقق')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_approved')
                    ->label('معتمد')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('preference_type')
                    ->label('النوع')
                    ->options(TenderPricePreference::getPreferenceTypes()),
                Tables\Filters\TernaryFilter::make('is_verified')
                    ->label('التحقق'),
                Tables\Filters\TernaryFilter::make('is_approved')
                    ->label('الاعتماد'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
