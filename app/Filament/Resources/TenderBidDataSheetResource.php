<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenderBidDataSheetResource\Pages;
use App\Models\TenderBidDataSheet;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TenderBidDataSheetResource extends Resource
{
    protected static ?string $model = TenderBidDataSheet::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    protected static ?string $navigationGroup = 'العطاءات والمناقصات';
    
    protected static ?string $navigationLabel = 'جداول بيانات المناقصة';
    
    protected static ?string $modelLabel = 'جدول بيانات المناقصة';
    
    protected static ?string $pluralModelLabel = 'جداول بيانات المناقصات';
    
    protected static ?int $navigationSort = 12;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('البيانات الأساسية')
                    ->schema([
                        Forms\Components\Select::make('tender_id')
                            ->relationship('tender', 'name_ar')
                            ->label('العطاء')
                            ->required()
                            ->searchable(),
                    ])->columns(1),
                    
                Forms\Components\Section::make('الجهات - ITB 1.1')
                    ->schema([
                        Forms\Components\TextInput::make('procuring_entity_ar')
                            ->label('الجهة المشترية (عربي)')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('procuring_entity_en')
                            ->label('الجهة المشترية (إنجليزي)')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('beneficiary_entity_ar')
                            ->label('الجهة المستفيدة (عربي)')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('beneficiary_entity_en')
                            ->label('الجهة المستفيدة (إنجليزي)')
                            ->maxLength(255),
                        Forms\Components\Textarea::make('works_description_ar')
                            ->label('وصف الأشغال (عربي)')
                            ->required()
                            ->rows(3),
                        Forms\Components\Textarea::make('works_description_en')
                            ->label('وصف الأشغال (إنجليزي)')
                            ->rows(3),
                        Forms\Components\TextInput::make('number_of_packages')
                            ->label('عدد الحزم')
                            ->numeric()
                            ->default(1),
                    ])->columns(2),
                    
                Forms\Components\Section::make('مصدر التمويل - ITB 2.1')
                    ->schema([
                        Forms\Components\Select::make('funding_source')
                            ->label('مصدر التمويل')
                            ->options(TenderBidDataSheet::FUNDING_SOURCES)
                            ->required(),
                        Forms\Components\TextInput::make('funding_details')
                            ->label('تفاصيل التمويل')
                            ->maxLength(500),
                        Forms\Components\TextInput::make('project_name')
                            ->label('اسم المشروع الممول')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('lender_name')
                            ->label('الجهة الممولة')
                            ->maxLength(255),
                    ])->columns(2),
                    
                Forms\Components\Section::make('الائتلاف - ITB 4.2')
                    ->schema([
                        Forms\Components\Toggle::make('consortium_allowed')
                            ->label('يسمح بالائتلاف'),
                        Forms\Components\TextInput::make('max_consortium_members')
                            ->label('الحد الأقصى لأعضاء الائتلاف')
                            ->numeric(),
                        Forms\Components\Textarea::make('consortium_requirements')
                            ->label('متطلبات الائتلاف')
                            ->rows(2),
                    ])->columns(2),
                    
                Forms\Components\Section::make('الاستيضاحات وزيارة الموقع')
                    ->schema([
                        Forms\Components\Textarea::make('clarification_address')
                            ->label('عنوان إرسال الاستيضاحات')
                            ->rows(2),
                        Forms\Components\TextInput::make('clarification_email')
                            ->label('بريد الاستيضاحات')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\DateTimePicker::make('clarification_deadline')
                            ->label('آخر موعد للاستيضاحات'),
                        Forms\Components\Toggle::make('site_visit_required')
                            ->label('زيارة الموقع إلزامية'),
                        Forms\Components\DateTimePicker::make('site_visit_date')
                            ->label('موعد زيارة الموقع'),
                        Forms\Components\Textarea::make('site_visit_location')
                            ->label('مكان التجمع')
                            ->rows(2),
                    ])->columns(2),
                    
                Forms\Components\Section::make('تأمين دخول العطاء - ITB 19')
                    ->schema([
                        Forms\Components\Select::make('bid_security_type')
                            ->label('نوع التأمين')
                            ->options(TenderBidDataSheet::SECURITY_TYPES),
                        Forms\Components\TextInput::make('bid_security_percentage')
                            ->label('نسبة التأمين %')
                            ->numeric()
                            ->suffix('%'),
                        Forms\Components\TextInput::make('bid_security_amount')
                            ->label('مبلغ التأمين')
                            ->numeric(),
                        Forms\Components\TextInput::make('bid_security_validity_days')
                            ->label('صلاحية التأمين (يوم)')
                            ->numeric(),
                        Forms\Components\Textarea::make('bid_security_beneficiary')
                            ->label('المستفيد من التأمين')
                            ->rows(2),
                    ])->columns(2),
                    
                Forms\Components\Section::make('التقديم والفتح')
                    ->schema([
                        Forms\Components\DateTimePicker::make('submission_deadline')
                            ->label('الموعد النهائي للتقديم')
                            ->required(),
                        Forms\Components\Textarea::make('submission_address')
                            ->label('عنوان التقديم')
                            ->required()
                            ->rows(2),
                        Forms\Components\Toggle::make('electronic_submission_allowed')
                            ->label('يسمح بالتقديم الإلكتروني'),
                        Forms\Components\TextInput::make('electronic_submission_url')
                            ->label('رابط التقديم الإلكتروني')
                            ->url()
                            ->maxLength(500),
                        Forms\Components\DateTimePicker::make('opening_date')
                            ->label('تاريخ فتح المظاريف')
                            ->required(),
                        Forms\Components\Textarea::make('opening_location')
                            ->label('مكان الفتح')
                            ->rows(2),
                        Forms\Components\Toggle::make('bidders_allowed_at_opening')
                            ->label('يسمح بحضور المناقصين')
                            ->default(true),
                    ])->columns(2),
                    
                Forms\Components\Section::make('متطلبات أخرى')
                    ->schema([
                        Forms\Components\Select::make('bid_language')
                            ->label('لغة العرض')
                            ->options([
                                'arabic' => 'العربية',
                                'english' => 'الإنجليزية',
                                'both' => 'كلاهما',
                            ])
                            ->default('arabic'),
                        Forms\Components\TextInput::make('bid_validity_days')
                            ->label('فترة سريان العرض (يوم)')
                            ->numeric()
                            ->default(90),
                        Forms\Components\TextInput::make('performance_security_percentage')
                            ->label('نسبة كفالة حسن التنفيذ %')
                            ->numeric()
                            ->default(10)
                            ->suffix('%'),
                        Forms\Components\Toggle::make('sme_preference_applicable')
                            ->label('تطبيق أفضلية المنشآت الصغيرة'),
                        Forms\Components\TextInput::make('sme_preference_percentage')
                            ->label('نسبة الأفضلية %')
                            ->numeric()
                            ->suffix('%'),
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
                    ->sortable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('procuring_entity_ar')
                    ->label('الجهة المشترية')
                    ->searchable()
                    ->limit(25),
                Tables\Columns\TextColumn::make('funding_source')
                    ->label('مصدر التمويل')
                    ->badge()
                    ->formatStateUsing(fn ($state) => TenderBidDataSheet::FUNDING_SOURCES[$state] ?? $state),
                Tables\Columns\TextColumn::make('submission_deadline')
                    ->label('موعد التقديم')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                Tables\Columns\IconColumn::make('consortium_allowed')
                    ->label('ائتلاف')
                    ->boolean(),
                Tables\Columns\IconColumn::make('electronic_submission_allowed')
                    ->label('إلكتروني')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('funding_source')
                    ->label('مصدر التمويل')
                    ->options(TenderBidDataSheet::FUNDING_SOURCES),
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
            'index' => Pages\ListTenderBidDataSheets::route('/'),
            'create' => Pages\CreateTenderBidDataSheet::route('/create'),
            'edit' => Pages\EditTenderBidDataSheet::route('/{record}/edit'),
        ];
    }
}
