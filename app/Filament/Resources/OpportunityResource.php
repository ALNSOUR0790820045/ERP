<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OpportunityResource\Pages;
use App\Models\Opportunity;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OpportunityResource extends Resource
{
    protected static ?string $model = Opportunity::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationGroup = 'العملاء (CRM)';

    protected static ?string $modelLabel = 'فرصة';

    protected static ?string $pluralModelLabel = 'الفرص';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الفرصة')
                    ->schema([
                        Forms\Components\TextInput::make('opportunity_number')
                            ->label('رقم الفرصة')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->default(fn () => Opportunity::generateNumber()),
                        Forms\Components\TextInput::make('opportunity_name')
                            ->label('اسم الفرصة')
                            ->required(),
                        Forms\Components\Select::make('customer_id')
                            ->label('العميل')
                            ->relationship('customer', 'company_name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('contact_id')
                            ->label('جهة الاتصال')
                            ->relationship('contact', 'name')
                            ->searchable()
                            ->preload(),
                    ])->columns(2),

                Forms\Components\Section::make('تفاصيل الفرصة')
                    ->schema([
                        Forms\Components\Select::make('source')
                            ->label('المصدر')
                            ->options([
                                'tender' => 'مناقصة',
                                'referral' => 'توصية',
                                'website' => 'الموقع',
                                'cold_call' => 'اتصال مباشر',
                                'exhibition' => 'معرض',
                                'other' => 'أخرى',
                            ]),
                        Forms\Components\Select::make('type')
                            ->label('نوع الفرصة')
                            ->options([
                                'tender' => 'مناقصة',
                                'direct' => 'تعاقد مباشر',
                                'partnership' => 'شراكة',
                                'subcontract' => 'عقد من الباطن',
                            ])
                            ->default('direct'),
                        Forms\Components\TextInput::make('estimated_value')
                            ->label('القيمة المتوقعة')
                            ->numeric()
                            ->required(),
                        Forms\Components\TextInput::make('probability')
                            ->label('احتمالية الفوز %')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(50),
                        Forms\Components\DatePicker::make('expected_close_date')
                            ->label('تاريخ الإغلاق المتوقع'),
                        Forms\Components\Select::make('stage')
                            ->label('المرحلة')
                            ->options([
                                'identification' => 'تحديد',
                                'qualification' => 'تأهيل',
                                'proposal' => 'عرض',
                                'negotiation' => 'تفاوض',
                                'won' => 'فوز',
                                'lost' => 'خسارة',
                            ])
                            ->default('identification')
                            ->required(),
                    ])->columns(3),

                Forms\Components\Section::make('المسؤولية')
                    ->schema([
                        Forms\Components\Select::make('assigned_to')
                            ->label('المسؤول')
                            ->relationship('assignedTo', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Textarea::make('description')
                            ->label('الوصف')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('lost_reason')
                            ->label('سبب الخسارة')
                            ->visible(fn ($get) => $get('stage') === 'lost'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('opportunity_number')
                    ->label('الرقم')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('opportunity_name')
                    ->label('اسم الفرصة')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('customer.company_name')
                    ->label('العميل')
                    ->searchable(),
                Tables\Columns\TextColumn::make('estimated_value')
                    ->label('القيمة المتوقعة')
                    ->money('JOD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('probability')
                    ->label('الاحتمالية')
                    ->suffix('%'),
                Tables\Columns\BadgeColumn::make('stage')
                    ->label('المرحلة')
                    ->colors([
                        'gray' => 'identification',
                        'info' => 'qualification',
                        'warning' => 'proposal',
                        'primary' => 'negotiation',
                        'success' => 'won',
                        'danger' => 'lost',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'identification' => 'تحديد',
                        'qualification' => 'تأهيل',
                        'proposal' => 'عرض',
                        'negotiation' => 'تفاوض',
                        'won' => 'فوز',
                        'lost' => 'خسارة',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('expected_close_date')
                    ->label('تاريخ الإغلاق')
                    ->date(),
                Tables\Columns\TextColumn::make('assignedTo.name')
                    ->label('المسؤول'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('stage')
                    ->label('المرحلة')
                    ->options([
                        'identification' => 'تحديد',
                        'qualification' => 'تأهيل',
                        'proposal' => 'عرض',
                        'negotiation' => 'تفاوض',
                        'won' => 'فوز',
                        'lost' => 'خسارة',
                    ]),
                Tables\Filters\SelectFilter::make('customer_id')
                    ->label('العميل')
                    ->relationship('customer', 'company_name')
                    ->searchable()
                    ->preload(),
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
            'index' => Pages\ListOpportunities::route('/'),
            'create' => Pages\CreateOpportunity::route('/create'),
            'edit' => Pages\EditOpportunity::route('/{record}/edit'),
        ];
    }
}
