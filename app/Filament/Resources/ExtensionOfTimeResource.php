<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExtensionOfTimeResource\Pages;
use App\Models\ExtensionOfTime;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ExtensionOfTimeResource extends Resource
{
    protected static ?string $model = ExtensionOfTime::class;
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationGroup = 'العقود';
    protected static ?int $navigationSort = 20;

    public static function getModelLabel(): string
    {
        return 'تمديد الوقت';
    }

    public static function getPluralModelLabel(): string
    {
        return 'تمديدات الوقت (EOT)';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('بيانات الطلب')->schema([
                Forms\Components\TextInput::make('eot_number')
                    ->label('رقم الطلب')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->default(fn() => 'EOT-' . date('Y') . '-' . str_pad(ExtensionOfTime::count() + 1, 3, '0', STR_PAD_LEFT)),
                Forms\Components\Select::make('contract_id')
                    ->label('العقد')
                    ->relationship('contract', 'contract_number')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('project_id')
                    ->label('المشروع')
                    ->relationship('project', 'name_ar')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\DatePicker::make('claim_date')
                    ->label('تاريخ الطلب')
                    ->required()
                    ->default(now()),
            ])->columns(2),

            Forms\Components\Section::make('تفاصيل الحدث')->schema([
                Forms\Components\Textarea::make('event_description')
                    ->label('وصف الحدث')
                    ->required()
                    ->rows(3)
                    ->columnSpan(2),
                Forms\Components\DatePicker::make('event_start_date')
                    ->label('تاريخ بداية الحدث')
                    ->required(),
                Forms\Components\DatePicker::make('event_end_date')
                    ->label('تاريخ نهاية الحدث'),
                Forms\Components\Select::make('delay_type')
                    ->label('نوع التأخير')
                    ->options([
                        'excusable_compensable' => 'معذور ويستحق تعويض',
                        'excusable_non_compensable' => 'معذور بدون تعويض',
                        'non_excusable' => 'غير معذور',
                        'concurrent' => 'تأخير متزامن',
                    ])
                    ->required(),
            ])->columns(2),

            Forms\Components\Section::make('الأيام والتكاليف')->schema([
                Forms\Components\TextInput::make('days_claimed')
                    ->label('الأيام المطلوبة')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('days_granted')
                    ->label('الأيام الممنوحة')
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('cost_claimed')
                    ->label('التكلفة المطلوبة')
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('cost_granted')
                    ->label('التكلفة الممنوحة')
                    ->numeric()
                    ->default(0),
            ])->columns(4),

            Forms\Components\Section::make('التواريخ المعدلة')->schema([
                Forms\Components\DatePicker::make('original_completion_date')
                    ->label('تاريخ الإنجاز الأصلي'),
                Forms\Components\DatePicker::make('revised_completion_date')
                    ->label('تاريخ الإنجاز المعدل'),
            ])->columns(2),

            Forms\Components\Section::make('التقييم والاعتماد')->schema([
                Forms\Components\Textarea::make('contractor_submission')
                    ->label('تقديم المقاول')
                    ->rows(3),
                Forms\Components\Textarea::make('engineer_assessment')
                    ->label('تقييم المهندس')
                    ->rows(3),
                Forms\Components\Select::make('status')
                    ->label('الحالة')
                    ->options([
                        'submitted' => 'مقدم',
                        'under_review' => 'قيد المراجعة',
                        'approved' => 'معتمد',
                        'partially_approved' => 'معتمد جزئياً',
                        'rejected' => 'مرفوض',
                    ])
                    ->default('submitted'),
                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات')
                    ->rows(2),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('eot_number')
                    ->label('رقم الطلب')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('contract.contract_number')
                    ->label('العقد')
                    ->sortable(),
                Tables\Columns\TextColumn::make('project.name_ar')
                    ->label('المشروع')
                    ->sortable(),
                Tables\Columns\TextColumn::make('claim_date')
                    ->label('تاريخ الطلب')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('delay_type')
                    ->label('نوع التأخير')
                    ->badge(),
                Tables\Columns\TextColumn::make('days_claimed')
                    ->label('المطلوب')
                    ->suffix(' يوم'),
                Tables\Columns\TextColumn::make('days_granted')
                    ->label('الممنوح')
                    ->suffix(' يوم')
                    ->color('success'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors([
                        'gray' => 'submitted',
                        'warning' => 'under_review',
                        'success' => 'approved',
                        'info' => 'partially_approved',
                        'danger' => 'rejected',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'submitted' => 'مقدم',
                        'under_review' => 'قيد المراجعة',
                        'approved' => 'معتمد',
                        'rejected' => 'مرفوض',
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
            'index' => Pages\ListExtensionOfTimes::route('/'),
            'create' => Pages\CreateExtensionOfTime::route('/create'),
            'edit' => Pages\EditExtensionOfTime::route('/{record}/edit'),
        ];
    }
}
