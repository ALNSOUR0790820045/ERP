<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenderTechnicalProposalResource\Pages;
use App\Models\TenderTechnicalProposal;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TenderTechnicalProposalResource extends Resource
{
    protected static ?string $model = TenderTechnicalProposal::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';
    
    protected static ?string $navigationGroup = 'العطاءات';
    
    protected static ?string $navigationLabel = 'العروض الفنية';
    
    protected static ?string $modelLabel = 'عرض فني';
    
    protected static ?string $pluralModelLabel = 'العروض الفنية';
    
    protected static ?int $navigationSort = 14;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('العطاء')
                    ->schema([
                        Forms\Components\Select::make('tender_id')
                            ->relationship('tender', 'name_ar')
                            ->label('العطاء')
                            ->required()
                            ->searchable(),
                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options(TenderTechnicalProposal::STATUSES)
                            ->default('draft'),
                        Forms\Components\TextInput::make('completeness_percentage')
                            ->label('نسبة الاكتمال')
                            ->numeric()
                            ->suffix('%')
                            ->disabled(),
                    ])->columns(3),
                    
                Forms\Components\Section::make('ملفات العرض الفني')
                    ->schema([
                        Forms\Components\FileUpload::make('company_profile_path')
                            ->label('ملف تعريف الشركة')
                            ->directory('technical-proposals'),
                        Forms\Components\FileUpload::make('organization_chart_path')
                            ->label('الهيكل التنظيمي')
                            ->directory('technical-proposals'),
                        Forms\Components\FileUpload::make('method_statement_path')
                            ->label('منهجية التنفيذ')
                            ->directory('technical-proposals'),
                        Forms\Components\FileUpload::make('work_program_path')
                            ->label('البرنامج الزمني')
                            ->directory('technical-proposals'),
                        Forms\Components\FileUpload::make('quality_plan_path')
                            ->label('خطة الجودة')
                            ->directory('technical-proposals'),
                        Forms\Components\FileUpload::make('safety_plan_path')
                            ->label('خطة السلامة')
                            ->directory('technical-proposals'),
                        Forms\Components\FileUpload::make('environmental_plan_path')
                            ->label('الخطة البيئية')
                            ->directory('technical-proposals'),
                    ])->columns(2),
                    
                Forms\Components\Section::make('القوائم المالية')
                    ->schema([
                        Forms\Components\FileUpload::make('financial_statements_path')
                            ->label('القوائم المالية')
                            ->directory('technical-proposals'),
                        Forms\Components\FileUpload::make('bank_reference_path')
                            ->label('خطاب البنك')
                            ->directory('technical-proposals'),
                        Forms\Components\TextInput::make('average_annual_turnover')
                            ->label('متوسط حجم الأعمال السنوي')
                            ->numeric(),
                        Forms\Components\TextInput::make('current_liquid_assets')
                            ->label('الأصول السائلة الحالية')
                            ->numeric(),
                    ])->columns(2),
                    
                Forms\Components\Section::make('الشهادات')
                    ->schema([
                        Forms\Components\FileUpload::make('classification_certificate_path')
                            ->label('شهادة التصنيف')
                            ->directory('technical-proposals'),
                        Forms\Components\TextInput::make('classification_category')
                            ->label('فئة التصنيف')
                            ->maxLength(50),
                        Forms\Components\DatePicker::make('classification_expiry')
                            ->label('تاريخ انتهاء التصنيف'),
                        Forms\Components\FileUpload::make('registration_certificate_path')
                            ->label('شهادة التسجيل')
                            ->directory('technical-proposals'),
                        Forms\Components\FileUpload::make('tax_clearance_path')
                            ->label('براءة الذمة الضريبية')
                            ->directory('technical-proposals'),
                        Forms\Components\DatePicker::make('tax_clearance_date')
                            ->label('تاريخ براءة الضريبة'),
                        Forms\Components\FileUpload::make('social_security_clearance_path')
                            ->label('براءة ذمة الضمان')
                            ->directory('technical-proposals'),
                        Forms\Components\DatePicker::make('social_security_clearance_date')
                            ->label('تاريخ براءة الضمان'),
                    ])->columns(2),
                    
                Forms\Components\Section::make('إحصائيات الخبرة')
                    ->schema([
                        Forms\Components\TextInput::make('total_similar_projects')
                            ->label('عدد المشاريع المماثلة')
                            ->numeric()
                            ->default(0),
                        Forms\Components\TextInput::make('total_similar_value')
                            ->label('إجمالي قيمة المشاريع المماثلة')
                            ->numeric(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tender.name_ar')
                    ->label('العطاء')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('classification_category')
                    ->label('فئة التصنيف')
                    ->badge(),
                Tables\Columns\TextColumn::make('classification_expiry')
                    ->label('انتهاء التصنيف')
                    ->date(),
                Tables\Columns\TextColumn::make('total_similar_projects')
                    ->label('مشاريع مماثلة')
                    ->numeric(),
                Tables\Columns\TextColumn::make('completeness_percentage')
                    ->label('نسبة الاكتمال')
                    ->suffix('%')
                    ->color(fn ($state) => $state >= 100 ? 'success' : ($state >= 50 ? 'warning' : 'danger')),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn ($state) => TenderTechnicalProposal::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'ready' => 'success',
                        'submitted' => 'primary',
                        default => 'warning',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(TenderTechnicalProposal::STATUSES),
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
            'index' => Pages\ListTenderTechnicalProposals::route('/'),
            'create' => Pages\CreateTenderTechnicalProposal::route('/create'),
            'edit' => Pages\EditTenderTechnicalProposal::route('/{record}/edit'),
        ];
    }
}
