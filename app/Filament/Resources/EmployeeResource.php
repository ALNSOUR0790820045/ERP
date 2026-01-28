<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;
use App\Models\Employee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'الموارد البشرية';
    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return 'موظف';
    }

    public static function getPluralModelLabel(): string
    {
        return 'الموظفين';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make('Tabs')->tabs([
                Forms\Components\Tabs\Tab::make('البيانات الشخصية')->schema([
                    Forms\Components\TextInput::make('employee_code')
                        ->label('رقم الموظف')
                        ->required()
                        ->unique(ignoreRecord: true),
                    Forms\Components\TextInput::make('first_name_ar')
                        ->label('الاسم الأول (عربي)')
                        ->required(),
                    Forms\Components\TextInput::make('last_name_ar')
                        ->label('اسم العائلة (عربي)')
                        ->required(),
                    Forms\Components\TextInput::make('first_name_en')
                        ->label('الاسم الأول (إنجليزي)'),
                    Forms\Components\TextInput::make('last_name_en')
                        ->label('اسم العائلة (إنجليزي)'),
                    Forms\Components\TextInput::make('national_id')
                        ->label('الرقم الوطني'),
                    Forms\Components\DatePicker::make('birth_date')
                        ->label('تاريخ الميلاد'),
                    Forms\Components\Select::make('gender')
                        ->label('الجنس')
                        ->options([
                            'male' => 'ذكر',
                            'female' => 'أنثى',
                        ]),
                    Forms\Components\Select::make('marital_status')
                        ->label('الحالة الاجتماعية')
                        ->options([
                            'single' => 'أعزب',
                            'married' => 'متزوج',
                            'divorced' => 'مطلق',
                            'widowed' => 'أرمل',
                        ]),
                    Forms\Components\TextInput::make('nationality')
                        ->label('الجنسية'),
                ])->columns(2),

                Forms\Components\Tabs\Tab::make('معلومات الاتصال')->schema([
                    Forms\Components\TextInput::make('phone')->label('الهاتف'),
                    Forms\Components\TextInput::make('mobile')->label('الجوال'),
                    Forms\Components\TextInput::make('email')->label('البريد الإلكتروني')->email(),
                    Forms\Components\Textarea::make('address')
                        ->label('العنوان')
                        ->rows(2)
                        ->columnSpan(2),
                ])->columns(2),

                Forms\Components\Tabs\Tab::make('بيانات العمل')->schema([
                    Forms\Components\Select::make('company_id')
                        ->label('الشركة')
                        ->relationship('company', 'name_ar'),
                    Forms\Components\Select::make('branch_id')
                        ->label('الفرع')
                        ->relationship('branch', 'name_ar'),
                    Forms\Components\Select::make('department_id')
                        ->label('القسم')
                        ->relationship('department', 'name_ar'),
                    Forms\Components\Select::make('job_title_id')
                        ->label('المسمى الوظيفي')
                        ->relationship('jobTitle', 'name_ar'),
                    Forms\Components\DatePicker::make('hire_date')
                        ->label('تاريخ التعيين')
                        ->required(),
                    Forms\Components\Select::make('employment_type')
                        ->label('نوع التوظيف')
                        ->options([
                            'full_time' => 'دوام كامل',
                            'part_time' => 'دوام جزئي',
                            'contract' => 'عقد',
                            'temporary' => 'مؤقت',
                        ])
                        ->default('full_time'),
                    Forms\Components\Select::make('employment_status')
                        ->label('حالة التوظيف')
                        ->options([
                            'active' => 'نشط',
                            'on_leave' => 'إجازة',
                            'suspended' => 'موقوف',
                            'terminated' => 'منتهي',
                        ])
                        ->default('active'),
                    Forms\Components\Select::make('direct_manager_id')
                        ->label('المدير المباشر')
                        ->relationship('directManager', 'first_name_ar'),
                    Forms\Components\Select::make('project_id')
                        ->label('المشروع')
                        ->relationship('project', 'name_ar'),
                ])->columns(2),

                Forms\Components\Tabs\Tab::make('الراتب والحساب البنكي')->schema([
                    Forms\Components\TextInput::make('basic_salary')
                        ->label('الراتب الأساسي')
                        ->numeric()
                        ->required(),
                    Forms\Components\Select::make('currency_id')
                        ->label('العملة')
                        ->relationship('currency', 'name_ar'),
                    Forms\Components\TextInput::make('bank_name')
                        ->label('اسم البنك'),
                    Forms\Components\TextInput::make('bank_account')
                        ->label('رقم الحساب'),
                    Forms\Components\TextInput::make('iban')
                        ->label('IBAN'),
                    Forms\Components\Toggle::make('is_active')
                        ->label('نشط')
                        ->default(true),
                ])->columns(2),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee_code')->label('الرقم')->searchable(),
                Tables\Columns\TextColumn::make('full_name_ar')->label('الاسم')
                    ->searchable(['first_name_ar', 'last_name_ar']),
                Tables\Columns\TextColumn::make('department.name_ar')->label('القسم'),
                Tables\Columns\TextColumn::make('jobTitle.name_ar')->label('المسمى الوظيفي'),
                Tables\Columns\TextColumn::make('employment_status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'on_leave' => 'warning',
                        'suspended' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('hire_date')->label('تاريخ التعيين')->date(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('department_id')
                    ->label('القسم')
                    ->relationship('department', 'name_ar'),
                Tables\Filters\SelectFilter::make('employment_status')
                    ->label('الحالة')
                    ->options([
                        'active' => 'نشط',
                        'on_leave' => 'إجازة',
                        'suspended' => 'موقوف',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }
}
