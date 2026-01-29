<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectMilestoneResource\Pages;
use App\Models\ProjectMilestone;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProjectMilestoneResource extends Resource
{
    protected static ?string $model = ProjectMilestone::class;
    protected static ?string $navigationIcon = 'heroicon-o-flag';
    protected static ?string $navigationGroup = 'المشاريع والعقود';
    protected static ?string $modelLabel = 'معلم المشروع';
    protected static ?string $pluralModelLabel = 'معالم المشاريع';
    protected static ?int $navigationSort = 15;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('بيانات المعلم')
                ->schema([
                    Forms\Components\Select::make('project_id')
                        ->label('المشروع')
                        ->relationship('project', 'name_ar')
                        ->required()
                        ->searchable()
                        ->preload(),
                    Forms\Components\Select::make('wbs_id')
                        ->label('عنصر WBS')
                        ->relationship('wbs', 'name')
                        ->searchable()
                        ->preload(),
                    Forms\Components\TextInput::make('name')
                        ->label('اسم المعلم')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Textarea::make('description')
                        ->label('الوصف')
                        ->rows(2),
                    Forms\Components\DatePicker::make('planned_date')
                        ->label('التاريخ المخطط')
                        ->required(),
                    Forms\Components\DatePicker::make('actual_date')
                        ->label('التاريخ الفعلي'),
                    Forms\Components\TextInput::make('weight')
                        ->label('الوزن (%)')
                        ->numeric()
                        ->suffix('%')
                        ->minValue(0)
                        ->maxValue(100),
                    Forms\Components\Toggle::make('is_payment_milestone')
                        ->label('معلم دفعة مالية'),
                    Forms\Components\TextInput::make('payment_percentage')
                        ->label('نسبة الدفعة (%)')
                        ->numeric()
                        ->suffix('%')
                        ->visible(fn ($get) => $get('is_payment_milestone')),
                    Forms\Components\Select::make('status')
                        ->label('الحالة')
                        ->options([
                            'pending' => 'قيد الانتظار',
                            'achieved' => 'محقق',
                            'delayed' => 'متأخر',
                            'cancelled' => 'ملغى',
                        ])
                        ->default('pending')
                        ->required(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('project.name_ar')
                    ->label('المشروع')
                    ->limit(25)
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم المعلم')
                    ->searchable(),
                Tables\Columns\TextColumn::make('planned_date')
                    ->label('التاريخ المخطط')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('actual_date')
                    ->label('التاريخ الفعلي')
                    ->date(),
                Tables\Columns\TextColumn::make('weight')
                    ->label('الوزن')
                    ->suffix('%'),
                Tables\Columns\IconColumn::make('is_payment_milestone')
                    ->label('دفعة مالية')
                    ->boolean(),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'pending' => 'gray',
                        'achieved' => 'success',
                        'delayed' => 'danger',
                        'cancelled' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pending' => 'قيد الانتظار',
                        'achieved' => 'محقق',
                        'delayed' => 'متأخر',
                        'cancelled' => 'ملغى',
                        default => $state,
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'pending' => 'قيد الانتظار',
                        'achieved' => 'محقق',
                        'delayed' => 'متأخر',
                        'cancelled' => 'ملغى',
                    ]),
                Tables\Filters\SelectFilter::make('project_id')
                    ->label('المشروع')
                    ->relationship('project', 'name_ar'),
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
            'index' => Pages\ListProjectMilestones::route('/'),
            'create' => Pages\CreateProjectMilestone::route('/create'),
            'edit' => Pages\EditProjectMilestone::route('/{record}/edit'),
        ];
    }
}
