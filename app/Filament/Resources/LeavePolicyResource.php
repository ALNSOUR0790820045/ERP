<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeavePolicyResource\Pages;
use App\Models\LeavePolicy;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LeavePolicyResource extends Resource
{
    protected static ?string $model = LeavePolicy::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-check';
    protected static ?string $navigationGroup = 'الموارد البشرية';
    protected static ?string $modelLabel = 'سياسة إجازات';
    protected static ?string $pluralModelLabel = 'سياسات الإجازات';
    protected static ?int $navigationSort = 25;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('بيانات السياسة')
                ->schema([
                    Forms\Components\Select::make('leave_type_id')
                        ->label('نوع الإجازة')
                        ->relationship('leaveType', 'name_ar')
                        ->required()
                        ->searchable()
                        ->preload(),
                    Forms\Components\Select::make('department_id')
                        ->label('القسم')
                        ->relationship('department', 'name_ar')
                        ->searchable()
                        ->preload()
                        ->helperText('اتركه فارغاً للتطبيق على جميع الأقسام'),
                    Forms\Components\Select::make('job_title_id')
                        ->label('المسمى الوظيفي')
                        ->relationship('jobTitle', 'name_ar')
                        ->searchable()
                        ->preload()
                        ->helperText('اتركه فارغاً للتطبيق على جميع المسميات'),
                    Forms\Components\TextInput::make('annual_entitlement')
                        ->label('الاستحقاق السنوي')
                        ->required()
                        ->numeric()
                        ->suffix('أيام'),
                    Forms\Components\TextInput::make('max_carry_forward')
                        ->label('الحد الأقصى للترحيل')
                        ->numeric()
                        ->suffix('أيام'),
                    Forms\Components\TextInput::make('max_accumulation')
                        ->label('الحد الأقصى للتراكم')
                        ->numeric()
                        ->suffix('أيام'),
                    Forms\Components\TextInput::make('min_service_months')
                        ->label('الحد الأدنى للخدمة')
                        ->numeric()
                        ->default(0)
                        ->suffix('شهور'),
                    Forms\Components\Toggle::make('is_paid')
                        ->label('مدفوعة الأجر')
                        ->default(true),
                    Forms\Components\Toggle::make('requires_attachment')
                        ->label('تتطلب مرفقات'),
                    Forms\Components\TextInput::make('max_consecutive_days')
                        ->label('الحد الأقصى للأيام المتتالية')
                        ->numeric(),
                    Forms\Components\TextInput::make('advance_notice_days')
                        ->label('إشعار مسبق')
                        ->numeric()
                        ->default(0)
                        ->suffix('أيام'),
                    Forms\Components\Textarea::make('conditions')
                        ->label('الشروط والأحكام')
                        ->rows(3)
                        ->columnSpanFull(),
                    Forms\Components\Toggle::make('is_active')
                        ->label('نشط')
                        ->default(true),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('leaveType.name_ar')
                    ->label('نوع الإجازة')
                    ->searchable(),
                Tables\Columns\TextColumn::make('department.name_ar')
                    ->label('القسم')
                    ->default('جميع الأقسام'),
                Tables\Columns\TextColumn::make('jobTitle.name_ar')
                    ->label('المسمى')
                    ->default('جميع المسميات'),
                Tables\Columns\TextColumn::make('annual_entitlement')
                    ->label('الاستحقاق')
                    ->suffix(' يوم'),
                Tables\Columns\IconColumn::make('is_paid')
                    ->label('مدفوعة')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('leave_type_id')
                    ->label('نوع الإجازة')
                    ->relationship('leaveType', 'name_ar'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('نشط'),
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
            'index' => Pages\ListLeavePolicies::route('/'),
            'create' => Pages\CreateLeavePolicy::route('/create'),
            'edit' => Pages\EditLeavePolicy::route('/{record}/edit'),
        ];
    }
}
