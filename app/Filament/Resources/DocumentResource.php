<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentResource\Pages;
use App\Models\Document;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;
    protected static ?string $navigationIcon = 'heroicon-o-folder-open';
    protected static ?string $navigationGroup = 'إدارة الوثائق';
    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return 'وثيقة';
    }

    public static function getPluralModelLabel(): string
    {
        return 'الوثائق';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('بيانات الوثيقة')->schema([
                Forms\Components\TextInput::make('document_number')
                    ->label('رقم الوثيقة')
                    ->required()
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('title')
                    ->label('العنوان')
                    ->required(),
                Forms\Components\Select::make('category_id')
                    ->label('التصنيف')
                    ->relationship('category', 'name_ar'),
                Forms\Components\Select::make('document_type')
                    ->label('نوع الوثيقة')
                    ->options([
                        'drawing' => 'رسم',
                        'specification' => 'مواصفات',
                        'report' => 'تقرير',
                        'procedure' => 'إجراء',
                        'form' => 'نموذج',
                        'contract' => 'عقد',
                        'letter' => 'خطاب',
                    ])
                    ->required(),
                Forms\Components\Select::make('project_id')
                    ->label('المشروع')
                    ->relationship('project', 'name_ar'),
                Forms\Components\TextInput::make('discipline')
                    ->label('التخصص'),
                Forms\Components\Textarea::make('description')
                    ->label('الوصف')
                    ->rows(2)
                    ->columnSpan(2),
            ])->columns(2),

            Forms\Components\Section::make('المراجعة والحالة')->schema([
                Forms\Components\TextInput::make('current_revision')
                    ->label('المراجعة الحالية')
                    ->default('0'),
                Forms\Components\Select::make('status')
                    ->label('الحالة')
                    ->options([
                        'draft' => 'مسودة',
                        'for_review' => 'للمراجعة',
                        'approved' => 'معتمد',
                        'superseded' => 'ملغى',
                    ])
                    ->default('draft'),
                Forms\Components\DatePicker::make('issue_date')
                    ->label('تاريخ الإصدار'),
                Forms\Components\DatePicker::make('effective_date')
                    ->label('تاريخ النفاذ'),
                Forms\Components\Select::make('confidentiality')
                    ->label('السرية')
                    ->options([
                        'public' => 'عام',
                        'internal' => 'داخلي',
                        'confidential' => 'سري',
                        'restricted' => 'مقيد',
                    ])
                    ->default('internal'),
                Forms\Components\Toggle::make('is_controlled')
                    ->label('وثيقة مضبوطة'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('document_number')->label('الرقم')->searchable(),
                Tables\Columns\TextColumn::make('title')->label('العنوان')->searchable()->limit(40),
                Tables\Columns\TextColumn::make('document_type')->label('النوع')->badge(),
                Tables\Columns\TextColumn::make('current_revision')->label('المراجعة'),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'for_review' => 'warning',
                        'approved' => 'success',
                        'superseded' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('project.name_ar')->label('المشروع'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('document_type')
                    ->options([
                        'drawing' => 'رسم',
                        'specification' => 'مواصفات',
                        'report' => 'تقرير',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'مسودة',
                        'approved' => 'معتمد',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDocuments::route('/'),
            'create' => Pages\CreateDocument::route('/create'),
            'edit' => Pages\EditDocument::route('/{record}/edit'),
        ];
    }
}
