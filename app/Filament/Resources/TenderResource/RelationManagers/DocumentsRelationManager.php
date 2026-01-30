<?php

namespace App\Filament\Resources\TenderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Storage;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'وثائق العطاء';
    
    protected static ?string $modelLabel = 'وثيقة';
    
    protected static ?string $pluralModelLabel = 'الوثائق';
    
    protected static ?string $icon = 'heroicon-o-document-duplicate';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الوثيقة')
                    ->icon('heroicon-o-document-text')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('اسم الوثيقة')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('مثال: المواصفات الفنية العامة'),
                            
                        Forms\Components\TextInput::make('document_number')
                            ->label('رقم الوثيقة')
                            ->maxLength(100)
                            ->placeholder('مثال: DOC-001'),
                            
                        Forms\Components\Select::make('document_type')
                            ->label('نوع الوثيقة')
                            ->options([
                                'tender_docs' => 'وثائق العطاء الرسمية',
                                'boq' => 'جدول الكميات',
                                'drawings' => 'المخططات',
                                'specifications' => 'المواصفات الفنية',
                                'contract' => 'وثائق العقد',
                                'addendum' => 'ملحق',
                                'clarification' => 'توضيح',
                                'correspondence' => 'مراسلات',
                                'submission' => 'وثائق التقديم',
                                'technical_proposal' => 'العرض الفني',
                                'financial_proposal' => 'العرض المالي',
                                'other' => 'أخرى',
                            ])
                            ->required()
                            ->native(false)
                            ->searchable(),
                            
                        Forms\Components\Select::make('category')
                            ->label('التصنيف')
                            ->options([
                                'received' => 'مستلمة من الجهة',
                                'prepared' => 'معدة للتقديم',
                                'internal' => 'داخلية',
                                'external' => 'خارجية',
                            ])
                            ->default('received')
                            ->native(false),
                    ]),
                    
                Forms\Components\Section::make('الملف')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->schema([
                        Forms\Components\FileUpload::make('file_path')
                            ->label('الملف')
                            ->directory('tender-documents')
                            ->preserveFilenames()
                            ->openable()
                            ->downloadable()
                            ->previewable()
                            ->maxSize(50 * 1024) // 50MB
                            ->columnSpanFull()
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/zip',
                                'application/x-rar-compressed',
                                'image/*',
                                'application/vnd.dwg',
                                'application/dxf',
                            ]),
                    ]),
                    
                Forms\Components\Section::make('تفاصيل إضافية')
                    ->icon('heroicon-o-information-circle')
                    ->columns(3)
                    ->collapsible()
                    ->schema([
                        Forms\Components\TextInput::make('version')
                            ->label('الإصدار')
                            ->default('1.0')
                            ->maxLength(20),
                            
                        Forms\Components\DatePicker::make('issue_date')
                            ->label('تاريخ الإصدار')
                            ->native(false),
                            
                        Forms\Components\DatePicker::make('received_date')
                            ->label('تاريخ الاستلام')
                            ->native(false),
                            
                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options([
                                'active' => 'فعالة',
                                'superseded' => 'مستبدلة',
                                'draft' => 'مسودة',
                                'archived' => 'مؤرشفة',
                            ])
                            ->default('active')
                            ->native(false),
                            
                        Forms\Components\Toggle::make('is_confidential')
                            ->label('سرية')
                            ->inline(false),
                            
                        Forms\Components\Toggle::make('requires_response')
                            ->label('تتطلب رد')
                            ->inline(false),
                    ]),
                    
                Forms\Components\Section::make('الوصف والملاحظات')
                    ->collapsed()
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('الوصف')
                            ->rows(2)
                            ->columnSpanFull()
                            ->placeholder('وصف مختصر للوثيقة'),
                            
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('created_at', 'desc')
            ->groups([
                Tables\Grouping\Group::make('document_type')
                    ->label('نوع الوثيقة')
                    ->getTitleFromRecordUsing(fn ($record) => match($record->document_type) {
                        'tender_docs' => 'وثائق العطاء الرسمية',
                        'boq' => 'جدول الكميات',
                        'drawings' => 'المخططات',
                        'specifications' => 'المواصفات الفنية',
                        'contract' => 'وثائق العقد',
                        'addendum' => 'ملحق',
                        'clarification' => 'توضيح',
                        'correspondence' => 'مراسلات',
                        'submission' => 'وثائق التقديم',
                        'technical_proposal' => 'العرض الفني',
                        'financial_proposal' => 'العرض المالي',
                        default => 'أخرى',
                    }),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('document_type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'tender_docs' => 'رسمية',
                        'boq' => 'BOQ',
                        'drawings' => 'مخططات',
                        'specifications' => 'مواصفات',
                        'contract' => 'عقد',
                        'addendum' => 'ملحق',
                        'clarification' => 'توضيح',
                        'correspondence' => 'مراسلات',
                        'submission' => 'تقديم',
                        'technical_proposal' => 'فني',
                        'financial_proposal' => 'مالي',
                        default => 'أخرى',
                    })
                    ->color(fn ($state) => match($state) {
                        'tender_docs' => 'primary',
                        'boq' => 'success',
                        'drawings' => 'info',
                        'specifications' => 'warning',
                        'contract' => 'danger',
                        'addendum', 'clarification' => 'purple',
                        'submission', 'technical_proposal', 'financial_proposal' => 'success',
                        default => 'gray',
                    })
                    ->icon(fn ($state) => match($state) {
                        'drawings' => 'heroicon-o-cube',
                        'boq' => 'heroicon-o-table-cells',
                        'contract' => 'heroicon-o-document-check',
                        default => 'heroicon-o-document',
                    }),
                    
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم الوثيقة')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(40)
                    ->tooltip(fn ($state) => $state),
                    
                Tables\Columns\TextColumn::make('document_number')
                    ->label('الرقم')
                    ->searchable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('version')
                    ->label('الإصدار')
                    ->badge()
                    ->color('gray'),
                    
                Tables\Columns\TextColumn::make('category')
                    ->label('التصنيف')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'received' => 'مستلمة',
                        'prepared' => 'معدة',
                        'internal' => 'داخلية',
                        'external' => 'خارجية',
                        default => $state,
                    })
                    ->color(fn ($state) => match($state) {
                        'received' => 'info',
                        'prepared' => 'success',
                        'internal' => 'gray',
                        'external' => 'warning',
                        default => 'gray',
                    })
                    ->toggleable(),
                    
                Tables\Columns\IconColumn::make('is_confidential')
                    ->label('سرية')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open')
                    ->trueColor('danger')
                    ->falseColor('gray')
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'active' => 'فعالة',
                        'superseded' => 'مستبدلة',
                        'draft' => 'مسودة',
                        'archived' => 'مؤرشفة',
                        default => $state,
                    })
                    ->color(fn ($state) => match($state) {
                        'active' => 'success',
                        'superseded' => 'warning',
                        'draft' => 'gray',
                        'archived' => 'danger',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الرفع')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('document_type')
                    ->label('نوع الوثيقة')
                    ->multiple()
                    ->options([
                        'tender_docs' => 'وثائق العطاء الرسمية',
                        'boq' => 'جدول الكميات',
                        'drawings' => 'المخططات',
                        'specifications' => 'المواصفات الفنية',
                        'contract' => 'وثائق العقد',
                        'addendum' => 'ملحق',
                        'clarification' => 'توضيح',
                        'correspondence' => 'مراسلات',
                        'submission' => 'وثائق التقديم',
                        'technical_proposal' => 'العرض الفني',
                        'financial_proposal' => 'العرض المالي',
                        'other' => 'أخرى',
                    ]),
                    
                Tables\Filters\SelectFilter::make('category')
                    ->label('التصنيف')
                    ->options([
                        'received' => 'مستلمة من الجهة',
                        'prepared' => 'معدة للتقديم',
                        'internal' => 'داخلية',
                        'external' => 'خارجية',
                    ]),
                    
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'active' => 'فعالة',
                        'superseded' => 'مستبدلة',
                        'draft' => 'مسودة',
                        'archived' => 'مؤرشفة',
                    ]),
                    
                Tables\Filters\TernaryFilter::make('is_confidential')
                    ->label('سرية'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->icon('heroicon-o-plus')
                    ->label('إضافة وثيقة'),
                    
                Tables\Actions\Action::make('upload_multiple')
                    ->label('رفع ملفات متعددة')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('success')
                    ->form([
                        Forms\Components\FileUpload::make('files')
                            ->label('الملفات')
                            ->multiple()
                            ->directory('tender-documents')
                            ->preserveFilenames()
                            ->maxFiles(20),
                        Forms\Components\Select::make('document_type')
                            ->label('نوع الوثائق')
                            ->options([
                                'tender_docs' => 'وثائق العطاء الرسمية',
                                'drawings' => 'المخططات',
                                'specifications' => 'المواصفات الفنية',
                                'other' => 'أخرى',
                            ])
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $tender = $this->getOwnerRecord();
                        $count = 0;
                        
                        foreach ($data['files'] ?? [] as $file) {
                            $tender->documents()->create([
                                'name' => pathinfo($file, PATHINFO_FILENAME),
                                'file_path' => $file,
                                'document_type' => $data['document_type'],
                                'category' => 'received',
                                'status' => 'active',
                            ]);
                            $count++;
                        }
                        
                        Notification::make()
                            ->title("تم رفع {$count} ملفات بنجاح")
                            ->success()
                            ->send();
                    }),
                    
                Tables\Actions\Action::make('statistics')
                    ->label('إحصائيات')
                    ->icon('heroicon-o-chart-bar')
                    ->color('info')
                    ->modalHeading('إحصائيات الوثائق')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('إغلاق')
                    ->modalContent(function () {
                        $tender = $this->getOwnerRecord();
                        $docs = $tender->documents;
                        
                        $total = $docs->count();
                        $byType = $docs->groupBy('document_type')->map->count();
                        $byCategory = $docs->groupBy('category')->map->count();
                        
                        return new HtmlString("
                            <div class='space-y-4'>
                                <div class='bg-primary-50 dark:bg-primary-900/20 p-4 rounded-lg text-center'>
                                    <div class='text-3xl font-bold text-primary-600'>{$total}</div>
                                    <div class='text-sm text-gray-600'>إجمالي الوثائق</div>
                                </div>
                                <div class='grid grid-cols-2 gap-4'>
                                    <div class='bg-gray-100 dark:bg-gray-800 p-3 rounded-lg'>
                                        <div class='font-bold mb-2'>حسب التصنيف</div>
                                        <div class='text-sm space-y-1'>
                                            <div>مستلمة: " . ($byCategory['received'] ?? 0) . "</div>
                                            <div>معدة: " . ($byCategory['prepared'] ?? 0) . "</div>
                                            <div>داخلية: " . ($byCategory['internal'] ?? 0) . "</div>
                                        </div>
                                    </div>
                                    <div class='bg-gray-100 dark:bg-gray-800 p-3 rounded-lg'>
                                        <div class='font-bold mb-2'>حسب النوع</div>
                                        <div class='text-sm space-y-1'>
                                            <div>مخططات: " . ($byType['drawings'] ?? 0) . "</div>
                                            <div>مواصفات: " . ($byType['specifications'] ?? 0) . "</div>
                                            <div>BOQ: " . ($byType['boq'] ?? 0) . "</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        ");
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    
                    Tables\Actions\Action::make('download')
                        ->label('تحميل')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->url(fn ($record) => $record->file_path ? Storage::url($record->file_path) : null)
                        ->openUrlInNewTab()
                        ->visible(fn ($record) => $record->file_path),
                        
                    Tables\Actions\Action::make('new_version')
                        ->label('إصدار جديد')
                        ->icon('heroicon-o-document-plus')
                        ->color('warning')
                        ->form([
                            Forms\Components\FileUpload::make('file_path')
                                ->label('الملف الجديد')
                                ->directory('tender-documents')
                                ->preserveFilenames()
                                ->required(),
                            Forms\Components\TextInput::make('version')
                                ->label('رقم الإصدار')
                                ->required(),
                        ])
                        ->action(function ($record, array $data) {
                            // Archive old version
                            $record->update(['status' => 'superseded']);
                            
                            // Create new version
                            $tender = $this->getOwnerRecord();
                            $tender->documents()->create([
                                'name' => $record->name,
                                'document_type' => $record->document_type,
                                'category' => $record->category,
                                'file_path' => $data['file_path'],
                                'version' => $data['version'],
                                'status' => 'active',
                            ]);
                            
                            Notification::make()
                                ->title('تم إنشاء إصدار جديد')
                                ->success()
                                ->send();
                        }),
                        
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('archive')
                        ->label('أرشفة المحدد')
                        ->icon('heroicon-o-archive-box')
                        ->color('warning')
                        ->action(function ($records) {
                            $records->each->update(['status' => 'archived']);
                            Notification::make()->title('تم الأرشفة')->success()->send();
                        }),
                ]),
            ])
            ->emptyStateHeading('لا توجد وثائق')
            ->emptyStateDescription('أضف وثائق العطاء والمخططات والمواصفات')
            ->emptyStateIcon('heroicon-o-document-duplicate')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('إضافة وثيقة جديدة')
                    ->icon('heroicon-o-plus'),
            ]);
    }
}
