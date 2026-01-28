<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IncidentResource\Pages;
use App\Models\Incident;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class IncidentResource extends Resource
{
    protected static ?string $model = Incident::class;
    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';
    protected static ?string $navigationGroup = 'السلامة والصحة المهنية';
    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return 'حادث';
    }

    public static function getPluralModelLabel(): string
    {
        return 'الحوادث';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('بيانات الحادث')->schema([
                Forms\Components\TextInput::make('incident_number')
                    ->label('رقم الحادث')
                    ->required()
                    ->unique(ignoreRecord: true),
                Forms\Components\DateTimePicker::make('incident_datetime')
                    ->label('تاريخ ووقت الحادث')
                    ->required(),
                Forms\Components\Select::make('project_id')
                    ->label('المشروع')
                    ->relationship('project', 'name_ar')
                    ->required(),
                Forms\Components\Select::make('incident_type')
                    ->label('نوع الحادث')
                    ->options([
                        'accident' => 'حادث',
                        'near_miss' => 'حادث وشيك',
                        'first_aid' => 'إسعافات أولية',
                        'property_damage' => 'ضرر بالممتلكات',
                    ])
                    ->required(),
                Forms\Components\Select::make('severity')
                    ->label('شدة الحادث')
                    ->options([
                        'minor' => 'بسيط',
                        'moderate' => 'متوسط',
                        'major' => 'كبير',
                        'fatal' => 'قاتل',
                    ])
                    ->default('minor'),
                Forms\Components\TextInput::make('location')
                    ->label('الموقع')
                    ->required(),
                Forms\Components\Textarea::make('description')
                    ->label('وصف الحادث')
                    ->required()
                    ->rows(3)
                    ->columnSpan(2),
            ])->columns(2),

            Forms\Components\Section::make('الإصابات والأضرار')->schema([
                Forms\Components\Toggle::make('injury_occurred')
                    ->label('حدثت إصابات'),
                Forms\Components\TextInput::make('injuries_count')
                    ->label('عدد الإصابات')
                    ->numeric()
                    ->default(0),
                Forms\Components\Textarea::make('injury_details')
                    ->label('تفاصيل الإصابات')
                    ->rows(2),
                Forms\Components\Toggle::make('property_damage')
                    ->label('ضرر بالممتلكات'),
                Forms\Components\TextInput::make('damage_cost')
                    ->label('تكلفة الأضرار')
                    ->numeric(),
                Forms\Components\Toggle::make('work_stopped')
                    ->label('توقف العمل'),
                Forms\Components\TextInput::make('lost_hours')
                    ->label('الساعات الضائعة')
                    ->numeric()
                    ->default(0),
            ])->columns(2),

            Forms\Components\Section::make('التحقيق والإجراءات')->schema([
                Forms\Components\Textarea::make('root_cause')
                    ->label('السبب الجذري')
                    ->rows(2),
                Forms\Components\Textarea::make('corrective_actions')
                    ->label('الإجراءات التصحيحية')
                    ->rows(2),
                Forms\Components\Textarea::make('preventive_actions')
                    ->label('الإجراءات الوقائية')
                    ->rows(2),
                Forms\Components\Select::make('status')
                    ->label('الحالة')
                    ->options([
                        'reported' => 'مُبلغ',
                        'investigating' => 'قيد التحقيق',
                        'closed' => 'مغلق',
                    ])
                    ->default('reported'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('incident_number')->label('الرقم')->searchable(),
                Tables\Columns\TextColumn::make('incident_datetime')->label('التاريخ')->dateTime(),
                Tables\Columns\TextColumn::make('project.name_ar')->label('المشروع'),
                Tables\Columns\TextColumn::make('incident_type')
                    ->label('النوع')
                    ->badge(),
                Tables\Columns\TextColumn::make('severity')
                    ->label('الشدة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'minor' => 'info',
                        'moderate' => 'warning',
                        'major' => 'danger',
                        'fatal' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('incident_type')
                    ->label('النوع')
                    ->options([
                        'accident' => 'حادث',
                        'near_miss' => 'حادث وشيك',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIncidents::route('/'),
            'create' => Pages\CreateIncident::route('/create'),
            'edit' => Pages\EditIncident::route('/{record}/edit'),
        ];
    }
}
