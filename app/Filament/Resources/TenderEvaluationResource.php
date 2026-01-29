<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenderEvaluationResource\Pages;
use App\Models\Tenders\TenderEvaluation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TenderEvaluationResource extends Resource
{
    protected static ?string $model = TenderEvaluation::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'العطاءات والمناقصات';
    protected static ?string $navigationLabel = 'تقييم المشاريع';
    protected static ?string $modelLabel = 'تقييم مشروع';
    protected static ?string $pluralModelLabel = 'تقييمات المشاريع';
    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('بيانات العطاء')->schema([
                Forms\Components\Select::make('tender_id')
                    ->label('العطاء')
                    ->relationship('tender', 'name_ar')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('passing_threshold')
                    ->label('حد النجاح (%)')
                    ->numeric()
                    ->default(60)
                    ->suffix('%'),
            ])->columns(2),

            Forms\Components\Section::make('معايير التقييم (1-5)')->schema([
                Forms\Components\Grid::make(3)->schema([
                    Forms\Components\Fieldset::make('التوافق الاستراتيجي')->schema([
                        Forms\Components\Select::make('strategic_alignment_score')
                            ->label('الدرجة')
                            ->options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5']),
                        Forms\Components\Textarea::make('strategic_alignment_notes')
                            ->label('ملاحظات')
                            ->rows(2),
                    ]),
                    Forms\Components\Fieldset::make('القدرة الفنية')->schema([
                        Forms\Components\Select::make('technical_capability_score')
                            ->label('الدرجة')
                            ->options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5']),
                        Forms\Components\Textarea::make('technical_capability_notes')
                            ->label('ملاحظات')
                            ->rows(2),
                    ]),
                    Forms\Components\Fieldset::make('الموارد البشرية')->schema([
                        Forms\Components\Select::make('human_resources_score')
                            ->label('الدرجة')
                            ->options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5']),
                        Forms\Components\Textarea::make('human_resources_notes')
                            ->label('ملاحظات')
                            ->rows(2),
                    ]),
                ]),
                Forms\Components\Grid::make(3)->schema([
                    Forms\Components\Fieldset::make('توفر المعدات')->schema([
                        Forms\Components\Select::make('equipment_availability_score')
                            ->label('الدرجة')
                            ->options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5']),
                        Forms\Components\Textarea::make('equipment_availability_notes')
                            ->label('ملاحظات')
                            ->rows(2),
                    ]),
                    Forms\Components\Fieldset::make('القدرة المالية')->schema([
                        Forms\Components\Select::make('financial_capacity_score')
                            ->label('الدرجة')
                            ->options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5']),
                        Forms\Components\Textarea::make('financial_capacity_notes')
                            ->label('ملاحظات')
                            ->rows(2),
                    ]),
                    Forms\Components\Fieldset::make('الخبرة المماثلة')->schema([
                        Forms\Components\Select::make('similar_experience_score')
                            ->label('الدرجة')
                            ->options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5']),
                        Forms\Components\Textarea::make('similar_experience_notes')
                            ->label('ملاحظات')
                            ->rows(2),
                    ]),
                ]),
                Forms\Components\Grid::make(3)->schema([
                    Forms\Components\Fieldset::make('العلاقة مع المالك')->schema([
                        Forms\Components\Select::make('owner_relationship_score')
                            ->label('الدرجة')
                            ->options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5']),
                        Forms\Components\Textarea::make('owner_relationship_notes')
                            ->label('ملاحظات')
                            ->rows(2),
                    ]),
                    Forms\Components\Fieldset::make('مستوى المنافسة')->schema([
                        Forms\Components\Select::make('competition_level_score')
                            ->label('الدرجة')
                            ->options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5']),
                        Forms\Components\Textarea::make('competition_level_notes')
                            ->label('ملاحظات')
                            ->rows(2),
                    ]),
                    Forms\Components\Fieldset::make('هامش الربح')->schema([
                        Forms\Components\Select::make('profit_margin_score')
                            ->label('الدرجة')
                            ->options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5']),
                        Forms\Components\TextInput::make('expected_profit_percentage')
                            ->label('الربح المتوقع %')
                            ->numeric()
                            ->suffix('%'),
                    ]),
                ]),
                Forms\Components\Grid::make(3)->schema([
                    Forms\Components\Fieldset::make('مستوى المخاطر')->schema([
                        Forms\Components\Select::make('risk_level_score')
                            ->label('الدرجة')
                            ->options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5']),
                        Forms\Components\Textarea::make('risk_level_notes')
                            ->label('ملاحظات')
                            ->rows(2),
                    ]),
                    Forms\Components\Fieldset::make('الموقع')->schema([
                        Forms\Components\Select::make('location_score')
                            ->label('الدرجة')
                            ->options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5']),
                        Forms\Components\Textarea::make('location_notes')
                            ->label('ملاحظات')
                            ->rows(2),
                    ]),
                    Forms\Components\Fieldset::make('الجدول الزمني')->schema([
                        Forms\Components\Select::make('timeline_feasibility_score')
                            ->label('الدرجة')
                            ->options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5']),
                        Forms\Components\Textarea::make('timeline_notes')
                            ->label('ملاحظات')
                            ->rows(2),
                    ]),
                ]),
            ]),

            Forms\Components\Section::make('النتيجة والتوصية')->schema([
                Forms\Components\TextInput::make('total_weighted_score')
                    ->label('النتيجة الإجمالية')
                    ->numeric()
                    ->suffix('%')
                    ->disabled(),
                Forms\Components\Select::make('recommendation')
                    ->label('التوصية')
                    ->options(TenderEvaluation::RECOMMENDATIONS),
                Forms\Components\Textarea::make('conditions')
                    ->label('الشروط (إن وجدت)')
                    ->rows(3)
                    ->columnSpan(2),
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
                    ->limit(30),
                Tables\Columns\TextColumn::make('total_weighted_score')
                    ->label('النتيجة')
                    ->suffix('%')
                    ->sortable()
                    ->color(fn($state) => $state >= 60 ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('recommendation')
                    ->label('التوصية')
                    ->badge()
                    ->formatStateUsing(fn($state) => TenderEvaluation::RECOMMENDATIONS[$state] ?? $state)
                    ->color(fn($state) => match($state) {
                        'strongly_go' => 'success',
                        'go' => 'info',
                        'conditional_go' => 'warning',
                        'no_go' => 'danger',
                        'defer' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('expected_profit_percentage')
                    ->label('الربح المتوقع')
                    ->suffix('%'),
                Tables\Columns\TextColumn::make('evaluator.name')
                    ->label('المقيّم'),
                Tables\Columns\TextColumn::make('evaluated_at')
                    ->label('تاريخ التقييم')
                    ->dateTime('Y-m-d'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('recommendation')
                    ->label('التوصية')
                    ->options(TenderEvaluation::RECOMMENDATIONS),
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
            'index' => Pages\ListTenderEvaluations::route('/'),
            'create' => Pages\CreateTenderEvaluation::route('/create'),
            'edit' => Pages\EditTenderEvaluation::route('/{record}/edit'),
        ];
    }
}
