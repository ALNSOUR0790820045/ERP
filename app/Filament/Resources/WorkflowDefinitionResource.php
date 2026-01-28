<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkflowDefinitionResource\Pages;
use App\Models\WorkflowDefinition;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WorkflowDefinitionResource extends Resource
{
    protected static ?string $model = WorkflowDefinition::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationGroup = 'الإعدادات';
    protected static ?string $modelLabel = 'سير عمل';
    protected static ?string $pluralModelLabel = 'سير العمل';
    protected static ?int $navigationSort = 30;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('تعريف سير العمل')
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
                        Forms\Components\Textarea::make('description')
                            ->label('الوصف')
                            ->rows(2),
                    ])->columns(2),

                Forms\Components\Section::make('الإعدادات')
                    ->schema([
                        Forms\Components\Select::make('entity_type')
                            ->label('نوع الكيان')
                            ->options(WorkflowDefinition::ENTITY_TYPES)
                            ->required()
                            ->searchable(),
                        Forms\Components\Select::make('trigger_event')
                            ->label('حدث التشغيل')
                            ->options(WorkflowDefinition::TRIGGER_EVENTS)
                            ->required(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('فعّال')
                            ->default(true),
                    ])->columns(3),

                Forms\Components\Section::make('الشروط')
                    ->schema([
                        Forms\Components\Repeater::make('conditions')
                            ->label('شروط التفعيل')
                            ->schema([
                                Forms\Components\TextInput::make('field')
                                    ->label('الحقل')
                                    ->required(),
                                Forms\Components\Select::make('operator')
                                    ->label('العملية')
                                    ->options([
                                        '=' => 'يساوي',
                                        '>' => 'أكبر من',
                                        '<' => 'أصغر من',
                                        '>=' => 'أكبر من أو يساوي',
                                        '<=' => 'أصغر من أو يساوي',
                                        '!=' => 'لا يساوي',
                                    ])
                                    ->required(),
                                Forms\Components\TextInput::make('value')
                                    ->label('القيمة')
                                    ->required(),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->collapsible(),
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
                    ->searchable(),
                Tables\Columns\TextColumn::make('entity_type')
                    ->label('نوع الكيان')
                    ->formatStateUsing(fn ($state) => WorkflowDefinition::ENTITY_TYPES[$state] ?? class_basename($state)),
                Tables\Columns\TextColumn::make('trigger_event')
                    ->label('الحدث')
                    ->formatStateUsing(fn ($state) => WorkflowDefinition::TRIGGER_EVENTS[$state] ?? $state),
                Tables\Columns\TextColumn::make('steps_count')
                    ->label('الخطوات')
                    ->counts('steps'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('فعّال')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('الحالة'),
            ])
            ->actions([
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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkflowDefinitions::route('/'),
            'create' => Pages\CreateWorkflowDefinition::route('/create'),
            'edit' => Pages\EditWorkflowDefinition::route('/{record}/edit'),
        ];
    }
}
