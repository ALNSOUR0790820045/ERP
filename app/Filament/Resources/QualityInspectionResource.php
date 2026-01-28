<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QualityInspectionResource\Pages;
use App\Models\QualityInspection;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class QualityInspectionResource extends Resource
{
    protected static ?string $model = QualityInspection::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-badge';

    protected static ?string $navigationGroup = 'الجودة';

    protected static ?string $modelLabel = 'فحص جودة';

    protected static ?string $pluralModelLabel = 'فحوصات الجودة';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الفحص')
                    ->schema([
                        Forms\Components\TextInput::make('inspection_number')
                            ->label('رقم الفحص')
                            ->required()
                            ->unique(ignoreRecord: true),
                        Forms\Components\DatePicker::make('inspection_date')
                            ->label('تاريخ الفحص')
                            ->required()
                            ->default(now()),
                        Forms\Components\Select::make('project_id')
                            ->label('المشروع')
                            ->relationship('project', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('checklist_id')
                            ->label('قائمة الفحص')
                            ->relationship('checklist', 'name')
                            ->searchable()
                            ->preload(),
                    ])->columns(2),

                Forms\Components\Section::make('تفاصيل الفحص')
                    ->schema([
                        Forms\Components\TextInput::make('location')
                            ->label('الموقع'),
                        Forms\Components\TextInput::make('activity')
                            ->label('النشاط'),
                        Forms\Components\TextInput::make('inspector_name')
                            ->label('اسم المفتش'),
                        Forms\Components\Select::make('result')
                            ->label('النتيجة')
                            ->options([
                                'pass' => 'ناجح',
                                'fail' => 'فاشل',
                                'conditional' => 'مشروط',
                                'pending' => 'معلق',
                            ])
                            ->default('pending'),
                    ])->columns(2),

                Forms\Components\Section::make('الملاحظات')
                    ->schema([
                        Forms\Components\Textarea::make('observations')
                            ->label('الملاحظات'),
                        Forms\Components\Textarea::make('corrective_actions')
                            ->label('الإجراءات التصحيحية'),
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات إضافية'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('inspection_number')
                    ->label('رقم الفحص')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('inspection_date')
                    ->label('التاريخ')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('project.name')
                    ->label('المشروع')
                    ->searchable(),
                Tables\Columns\TextColumn::make('activity')
                    ->label('النشاط'),
                Tables\Columns\TextColumn::make('inspector_name')
                    ->label('المفتش'),
                Tables\Columns\BadgeColumn::make('result')
                    ->label('النتيجة')
                    ->colors([
                        'success' => 'pass',
                        'danger' => 'fail',
                        'warning' => 'conditional',
                        'gray' => 'pending',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pass' => 'ناجح',
                        'fail' => 'فاشل',
                        'conditional' => 'مشروط',
                        'pending' => 'معلق',
                        default => $state,
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('result')
                    ->label('النتيجة')
                    ->options([
                        'pass' => 'ناجح',
                        'fail' => 'فاشل',
                        'conditional' => 'مشروط',
                        'pending' => 'معلق',
                    ]),
                Tables\Filters\SelectFilter::make('project_id')
                    ->label('المشروع')
                    ->relationship('project', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListQualityInspections::route('/'),
            'create' => Pages\CreateQualityInspection::route('/create'),
            'edit' => Pages\EditQualityInspection::route('/{record}/edit'),
        ];
    }
}
