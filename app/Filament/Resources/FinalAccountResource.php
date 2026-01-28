<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FinalAccountResource\Pages;
use App\Models\FinalAccount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FinalAccountResource extends Resource
{
    protected static ?string $model = FinalAccount::class;
    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    protected static ?string $navigationGroup = 'المستخلصات';
    protected static ?int $navigationSort = 30;

    public static function getModelLabel(): string
    {
        return 'الحساب الختامي';
    }

    public static function getPluralModelLabel(): string
    {
        return 'الحسابات الختامية';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('بيانات الحساب الختامي')->schema([
                Forms\Components\TextInput::make('account_number')
                    ->label('رقم الحساب')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->default(fn() => 'FA-' . date('Y') . '-' . str_pad(FinalAccount::count() + 1, 3, '0', STR_PAD_LEFT)),
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
                Forms\Components\DatePicker::make('account_date')
                    ->label('تاريخ الحساب')
                    ->required()
                    ->default(now()),
            ])->columns(2),

            Forms\Components\Section::make('قيمة العقد')->schema([
                Forms\Components\TextInput::make('original_contract_value')
                    ->label('قيمة العقد الأصلية')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('variations_value')
                    ->label('قيمة الأوامر التغييرية')
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('revised_contract_value')
                    ->label('قيمة العقد المعدلة')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('final_measured_value')
                    ->label('القيمة النهائية المقاسة')
                    ->numeric()
                    ->required(),
            ])->columns(4),

            Forms\Components\Section::make('المطالبات والإجمالي')->schema([
                Forms\Components\TextInput::make('claims_value')
                    ->label('قيمة المطالبات')
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('total_value')
                    ->label('القيمة الإجمالية')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('total_certified')
                    ->label('إجمالي المعتمد')
                    ->numeric()
                    ->required(),
            ])->columns(3),

            Forms\Components\Section::make('المحتجزات والسلف')->schema([
                Forms\Components\TextInput::make('retention_held')
                    ->label('المحتجز')
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('retention_released')
                    ->label('المحتجز المصروف')
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('advance_issued')
                    ->label('السلفة الصادرة')
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('advance_recovered')
                    ->label('السلفة المستردة')
                    ->numeric()
                    ->default(0),
            ])->columns(4),

            Forms\Components\Section::make('الخصومات والمستحق')->schema([
                Forms\Components\TextInput::make('liquidated_damages')
                    ->label('غرامات التأخير')
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('other_deductions')
                    ->label('خصومات أخرى')
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('final_payable')
                    ->label('المستحق النهائي')
                    ->numeric()
                    ->required(),
                Forms\Components\Select::make('currency_id')
                    ->label('العملة')
                    ->relationship('currency', 'name_ar')
                    ->default(1),
            ])->columns(4),

            Forms\Components\Section::make('الحالة والاعتماد')->schema([
                Forms\Components\Select::make('status')
                    ->label('الحالة')
                    ->options([
                        'draft' => 'مسودة',
                        'submitted' => 'مقدم',
                        'under_review' => 'قيد المراجعة',
                        'approved' => 'معتمد',
                        'closed' => 'مغلق',
                    ])
                    ->default('draft'),
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
                Tables\Columns\TextColumn::make('account_number')
                    ->label('رقم الحساب')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('contract.contract_number')
                    ->label('العقد')
                    ->sortable(),
                Tables\Columns\TextColumn::make('project.name_ar')
                    ->label('المشروع')
                    ->sortable(),
                Tables\Columns\TextColumn::make('account_date')
                    ->label('التاريخ')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('original_contract_value')
                    ->label('قيمة العقد')
                    ->money('JOD'),
                Tables\Columns\TextColumn::make('total_value')
                    ->label('الإجمالي')
                    ->money('JOD'),
                Tables\Columns\TextColumn::make('final_payable')
                    ->label('المستحق النهائي')
                    ->money('JOD')
                    ->color('success'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors([
                        'gray' => 'draft',
                        'warning' => 'submitted',
                        'info' => 'under_review',
                        'success' => 'approved',
                        'primary' => 'closed',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'draft' => 'مسودة',
                        'submitted' => 'مقدم',
                        'approved' => 'معتمد',
                        'closed' => 'مغلق',
                    ]),
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
            'index' => Pages\ListFinalAccounts::route('/'),
            'create' => Pages\CreateFinalAccount::route('/create'),
            'edit' => Pages\EditFinalAccount::route('/{record}/edit'),
        ];
    }
}
