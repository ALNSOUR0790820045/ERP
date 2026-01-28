<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkPermitResource\Pages;
use App\Models\WorkPermit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WorkPermitResource extends Resource
{
    protected static ?string $model = WorkPermit::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'السلامة والصحة المهنية';

    protected static ?string $modelLabel = 'تصريح عمل';

    protected static ?string $pluralModelLabel = 'تصاريح العمل';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات التصريح')
                    ->schema([
                        Forms\Components\TextInput::make('permit_number')
                            ->label('رقم التصريح')
                            ->required()
                            ->unique(ignoreRecord: true),
                        Forms\Components\Select::make('permit_type')
                            ->label('نوع التصريح')
                            ->options([
                                'hot_work' => 'أعمال ساخنة',
                                'confined_space' => 'أماكن محصورة',
                                'height_work' => 'العمل على ارتفاعات',
                                'excavation' => 'الحفر',
                                'electrical' => 'كهرباء',
                                'lifting' => 'الرفع',
                                'general' => 'عام',
                            ])
                            ->required(),
                        Forms\Components\Select::make('project_id')
                            ->label('المشروع')
                            ->relationship('project', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('location')
                            ->label('الموقع'),
                    ])->columns(2),

                Forms\Components\Section::make('الفترة')
                    ->schema([
                        Forms\Components\DateTimePicker::make('start_datetime')
                            ->label('بداية التصريح')
                            ->required(),
                        Forms\Components\DateTimePicker::make('end_datetime')
                            ->label('نهاية التصريح')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('تفاصيل العمل')
                    ->schema([
                        Forms\Components\Textarea::make('work_description')
                            ->label('وصف العمل')
                            ->required(),
                        Forms\Components\Textarea::make('hazards')
                            ->label('المخاطر المحتملة'),
                        Forms\Components\Textarea::make('precautions')
                            ->label('احتياطات السلامة'),
                        Forms\Components\Textarea::make('ppe_required')
                            ->label('معدات الحماية المطلوبة'),
                    ]),

                Forms\Components\Section::make('الموافقات')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options([
                                'draft' => 'مسودة',
                                'pending' => 'قيد الاعتماد',
                                'approved' => 'معتمد',
                                'active' => 'نشط',
                                'completed' => 'مكتمل',
                                'cancelled' => 'ملغي',
                            ])
                            ->default('draft'),
                        Forms\Components\TextInput::make('requestor_name')
                            ->label('اسم مقدم الطلب'),
                        Forms\Components\TextInput::make('supervisor_name')
                            ->label('اسم المشرف'),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('permit_number')
                    ->label('رقم التصريح')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('permit_type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'hot_work' => 'أعمال ساخنة',
                        'confined_space' => 'أماكن محصورة',
                        'height_work' => 'ارتفاعات',
                        'excavation' => 'حفر',
                        'electrical' => 'كهرباء',
                        'lifting' => 'رفع',
                        'general' => 'عام',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('project.name')
                    ->label('المشروع'),
                Tables\Columns\TextColumn::make('start_datetime')
                    ->label('البداية')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_datetime')
                    ->label('النهاية')
                    ->dateTime(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors([
                        'gray' => 'draft',
                        'warning' => 'pending',
                        'info' => 'approved',
                        'success' => 'active',
                        'primary' => 'completed',
                        'danger' => 'cancelled',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('permit_type')
                    ->label('النوع')
                    ->options([
                        'hot_work' => 'أعمال ساخنة',
                        'confined_space' => 'أماكن محصورة',
                        'height_work' => 'ارتفاعات',
                        'excavation' => 'حفر',
                        'electrical' => 'كهرباء',
                        'lifting' => 'رفع',
                        'general' => 'عام',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'draft' => 'مسودة',
                        'pending' => 'قيد الاعتماد',
                        'approved' => 'معتمد',
                        'active' => 'نشط',
                        'completed' => 'مكتمل',
                        'cancelled' => 'ملغي',
                    ]),
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
            'index' => Pages\ListWorkPermits::route('/'),
            'create' => Pages\CreateWorkPermit::route('/create'),
            'edit' => Pages\EditWorkPermit::route('/{record}/edit'),
        ];
    }
}
