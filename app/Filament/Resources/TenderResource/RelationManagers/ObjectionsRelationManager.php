<?php

namespace App\Filament\Resources\TenderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Tenders\TenderObjection;

class ObjectionsRelationManager extends RelationManager
{
    protected static string $relationship = 'objections';

    protected static ?string $recordTitleAttribute = 'objection_number';
    
    protected static ?string $title = 'الاعتراضات';
    
    protected static ?string $modelLabel = 'اعتراض';
    
    protected static ?string $pluralModelLabel = 'الاعتراضات';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('بيانات الاعتراض')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('objection_number')
                            ->label('رقم الاعتراض')
                            ->required()
                            ->maxLength(50),
                        Forms\Components\Select::make('objection_type')
                            ->label('نوع الاعتراض')
                            ->options(TenderObjection::getObjectionTypes())
                            ->required(),
                        Forms\Components\TextInput::make('objector_name')
                            ->label('اسم المعترض')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('objector_company')
                            ->label('شركة المعترض')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('objector_email')
                            ->label('البريد الإلكتروني')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('objector_phone')
                            ->label('الهاتف')
                            ->tel()
                            ->maxLength(20),
                    ]),
                    
                Forms\Components\Section::make('تفاصيل الاعتراض')
                    ->schema([
                        Forms\Components\Textarea::make('objection_subject')
                            ->label('موضوع الاعتراض')
                            ->required()
                            ->rows(2),
                        Forms\Components\Textarea::make('objection_details')
                            ->label('تفاصيل الاعتراض')
                            ->required()
                            ->rows(4),
                        Forms\Components\Textarea::make('legal_basis')
                            ->label('السند القانوني')
                            ->rows(2),
                        Forms\Components\Textarea::make('supporting_documents')
                            ->label('المستندات الداعمة')
                            ->rows(2),
                    ]),
                    
                Forms\Components\Section::make('المعالجة')
                    ->columns(2)
                    ->schema([
                        Forms\Components\DatePicker::make('submitted_date')
                            ->label('تاريخ التقديم')
                            ->required(),
                        Forms\Components\DatePicker::make('response_deadline')
                            ->label('الموعد النهائي للرد'),
                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options([
                                'submitted' => 'مقدم',
                                'under_review' => 'قيد المراجعة',
                                'additional_info_requested' => 'طلب معلومات إضافية',
                                'accepted' => 'مقبول',
                                'rejected' => 'مرفوض',
                                'escalated' => 'محال للجنة الشكاوى',
                                'closed' => 'مغلق',
                            ])
                            ->default('submitted'),
                        Forms\Components\DatePicker::make('response_date')
                            ->label('تاريخ الرد'),
                        Forms\Components\Textarea::make('response_details')
                            ->label('تفاصيل الرد')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
                    
                Forms\Components\Section::make('التصعيد للجنة الشكاوى')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        Forms\Components\Toggle::make('escalated_to_committee')
                            ->label('محال للجنة الشكاوى')
                            ->live(),
                        Forms\Components\TextInput::make('escalation_fee_paid')
                            ->label('رسم الإحالة المدفوع')
                            ->numeric()
                            ->prefix('د.أ')
                            ->visible(fn (Forms\Get $get) => $get('escalated_to_committee')),
                        Forms\Components\DatePicker::make('escalation_date')
                            ->label('تاريخ الإحالة')
                            ->visible(fn (Forms\Get $get) => $get('escalated_to_committee')),
                        Forms\Components\Textarea::make('committee_decision')
                            ->label('قرار اللجنة')
                            ->rows(2)
                            ->columnSpanFull()
                            ->visible(fn (Forms\Get $get) => $get('escalated_to_committee')),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('objection_number')
            ->columns([
                Tables\Columns\TextColumn::make('objection_number')
                    ->label('رقم الاعتراض')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('objection_type')
                    ->label('النوع')
                    ->formatStateUsing(fn ($state) => TenderObjection::getObjectionTypes()[$state] ?? $state),
                Tables\Columns\TextColumn::make('objector_name')
                    ->label('المعترض')
                    ->searchable(),
                Tables\Columns\TextColumn::make('submitted_date')
                    ->label('تاريخ التقديم')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'submitted' => 'info',
                        'under_review' => 'warning',
                        'accepted' => 'success',
                        'rejected' => 'danger',
                        'escalated' => 'gray',
                        'closed' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('escalated_to_committee')
                    ->label('محال للجنة')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'submitted' => 'مقدم',
                        'under_review' => 'قيد المراجعة',
                        'accepted' => 'مقبول',
                        'rejected' => 'مرفوض',
                        'escalated' => 'محال للجنة الشكاوى',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
