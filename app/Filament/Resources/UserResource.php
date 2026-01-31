<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Branch;
use App\Models\Role;
use App\Models\SystemModule;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'إدارة الصلاحيات والوصول';
    protected static ?string $navigationLabel = 'المستخدمين';
    protected static ?string $modelLabel = 'مستخدم';
    protected static ?string $pluralModelLabel = 'المستخدمين';
    protected static ?int $navigationSort = 1;
    
    // إظهار في القائمة للوصول السريع
    protected static bool $shouldRegisterNavigation = true;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // البطاقة الرئيسية
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                // الصورة الشخصية
                                Forms\Components\FileUpload::make('avatar')
                                    ->label('')
                                    ->image()
                                    ->avatar()
                                    ->directory('avatars')
                                    ->circleCropper()
                                    ->columnSpan(1),
                                    
                                // البيانات الأساسية
                                Forms\Components\Grid::make(1)
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('الاسم الكامل')
                                            ->placeholder('أدخل الاسم الكامل')
                                            ->required()
                                            ->maxLength(255)
                                            ->autofocus(),
                                        Forms\Components\TextInput::make('email')
                                            ->label('البريد الإلكتروني')
                                            ->placeholder('example@company.com')
                                            ->email()
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(255),
                                    ])
                                    ->columnSpan(2),
                            ]),
                    ]),

                // معلومات الدخول
                Forms\Components\Section::make('بيانات الدخول')
                    ->icon('heroicon-o-key')
                    ->description('معلومات تسجيل الدخول للنظام')
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        Forms\Components\TextInput::make('username')
                            ->label('اسم المستخدم')
                            ->placeholder('اختياري - يمكن الدخول بالبريد')
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->helperText('يمكن تركه فارغاً والدخول بالبريد الإلكتروني'),
                        Forms\Components\TextInput::make('phone')
                            ->label('رقم الجوال')
                            ->placeholder('+962 7X XXX XXXX')
                            ->tel()
                            ->maxLength(20),
                        Forms\Components\TextInput::make('password')
                            ->label('كلمة المرور')
                            ->password()
                            ->revealable()
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->minLength(8)
                            ->helperText(fn (string $operation) => $operation === 'edit' ? 'اتركها فارغة للإبقاء على كلمة المرور الحالية' : 'على الأقل 8 أحرف'),
                        Forms\Components\TextInput::make('password_confirmation')
                            ->label('تأكيد كلمة المرور')
                            ->password()
                            ->revealable()
                            ->same('password')
                            ->required(fn (string $operation): bool => $operation === 'create'),
                    ]),

                // الدور والصلاحيات
                Forms\Components\Section::make('الأدوار والصلاحيات')
                    ->icon('heroicon-o-shield-check')
                    ->description('حدد أدوار المستخدم - يمكن اختيار دور وظيفي ودور عطاءات معاً')
                    ->columns(2)
                    ->schema([
                        // الأدوار الوظيفية
                        Forms\Components\Select::make('job_roles')
                            ->label('الدور الوظيفي')
                            ->multiple()
                            ->options(function () {
                                return Role::where('type', 'job')
                                    ->where('is_active', true)
                                    ->orderBy('level', 'desc')
                                    ->get()
                                    ->mapWithKeys(fn ($role) => [
                                        $role->id => $role->name_ar
                                    ]);
                            })
                            ->searchable()
                            ->preload()
                            ->helperText('الأدوار الوظيفية تحدد الوحدات والشاشات')
                            ->columnSpanFull(),
                            
                        // أدوار العطاءات
                        Forms\Components\Select::make('tender_roles')
                            ->label('دور العطاءات')
                            ->multiple()
                            ->options(function () {
                                return Role::where('type', 'tender')
                                    ->where('is_active', true)
                                    ->orderBy('level', 'desc')
                                    ->get()
                                    ->mapWithKeys(fn ($role) => [
                                        $role->id => $role->name_ar . ' (المستوى: ' . $role->level . ')'
                                    ]);
                            })
                            ->searchable()
                            ->preload()
                            ->helperText('أدوار العطاءات تحدد صلاحيات المراحل والموافقات')
                            ->columnSpanFull(),
                            
                        Forms\Components\Select::make('branch_id')
                            ->label('الفرع')
                            ->options(Branch::orderBy('name_ar')->pluck('name_ar', 'id'))
                            ->searchable()
                            ->preload()
                            ->placeholder('اختر الفرع (اختياري)'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('حساب نشط')
                            ->default(true)
                            ->inline(false)
                            ->helperText('المستخدم غير النشط لا يمكنه تسجيل الدخول'),
                        Forms\Components\Toggle::make('must_change_password')
                            ->label('تغيير كلمة المرور عند الدخول')
                            ->default(true)
                            ->inline(false)
                            ->helperText('يُطلب من المستخدم تغيير كلمة المرور عند أول تسجيل دخول'),
                    ]),

                // معلومات إضافية
                Forms\Components\Section::make('معلومات إضافية')
                    ->icon('heroicon-o-information-circle')
                    ->collapsed()
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('name_ar')
                            ->label('الاسم بالعربي')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('name_en')
                            ->label('الاسم بالإنجليزي')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('job_title')
                            ->label('المسمى الوظيفي')
                            ->placeholder('مثال: مدير مشاريع')
                            ->maxLength(100),
                        Forms\Components\Select::make('language')
                            ->label('لغة الواجهة')
                            ->options([
                                'ar' => '🇯🇴 العربية',
                                'en' => '🇬🇧 English',
                            ])
                            ->default('ar'),
                        Forms\Components\Select::make('timezone')
                            ->label('المنطقة الزمنية')
                            ->options([
                                'Asia/Amman' => '🇯🇴 الأردن (GMT+3)',
                                'Asia/Riyadh' => '🇸🇦 السعودية (GMT+3)',
                                'Asia/Dubai' => '🇦🇪 الإمارات (GMT+4)',
                                'Asia/Kuwait' => '🇰🇼 الكويت (GMT+3)',
                                'Asia/Qatar' => '🇶🇦 قطر (GMT+3)',
                            ])
                            ->default('Asia/Amman'),
                        Forms\Components\Toggle::make('two_factor_enabled')
                            ->label('التحقق بخطوتين')
                            ->inline(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('')
                    ->circular()
                    ->size(40)
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&background=random&size=40'),
                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record) => $record->email),
                // الأدوار الوظيفية
                Tables\Columns\TextColumn::make('jobRoles.name_ar')
                    ->label('الدور الوظيفي')
                    ->badge()
                    ->color('success')
                    ->separator(', ')
                    ->placeholder('—'),
                // أدوار العطاءات
                Tables\Columns\TextColumn::make('tenderRoles.name_ar')
                    ->label('دور العطاءات')
                    ->badge()
                    ->color('info')
                    ->separator(', ')
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('branch.name_ar')
                    ->label('الفرع')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('الحالة')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('last_login_at')
                    ->label('آخر دخول')
                    ->since()
                    ->placeholder('لم يسجل دخول')
                    ->sortable()
                    ->color(fn ($state) => $state ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->date('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('role_id')
                    ->label('الدور')
                    ->relationship('role', 'name_ar')
                    ->preload()
                    ->multiple(),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('الحالة')
                    ->placeholder('الكل')
                    ->trueLabel('نشط فقط')
                    ->falseLabel('معطل فقط'),
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label('الفرع')
                    ->relationship('branch', 'name_ar')
                    ->preload(),
                Tables\Filters\Filter::make('never_logged_in')
                    ->label('لم يسجل دخول')
                    ->query(fn (Builder $query) => $query->whereNull('last_login_at')),
            ])
            ->filtersFormColumns(2)
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('عرض'),
                    Tables\Actions\EditAction::make()
                        ->label('تعديل'),
                    Tables\Actions\Action::make('toggle_active')
                        ->label(fn ($record) => $record->is_active ? 'تعطيل الحساب' : 'تفعيل الحساب')
                        ->icon(fn ($record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                        ->color(fn ($record) => $record->is_active ? 'danger' : 'success')
                        ->requiresConfirmation()
                        ->modalDescription(fn ($record) => $record->is_active 
                            ? 'سيتم تعطيل حساب هذا المستخدم ولن يتمكن من تسجيل الدخول'
                            : 'سيتم تفعيل حساب هذا المستخدم وسيتمكن من تسجيل الدخول')
                        ->action(function ($record) {
                            $record->update(['is_active' => !$record->is_active]);
                            Notification::make()
                                ->success()
                                ->title($record->is_active ? 'تم تفعيل الحساب' : 'تم تعطيل الحساب')
                                ->send();
                        }),
                    Tables\Actions\Action::make('reset_password')
                        ->label('إعادة تعيين كلمة المرور')
                        ->icon('heroicon-o-key')
                        ->color('warning')
                        ->form([
                            Forms\Components\TextInput::make('new_password')
                                ->label('كلمة المرور الجديدة')
                                ->password()
                                ->revealable()
                                ->required()
                                ->minLength(8)
                                ->default(fn () => 'Pass@' . rand(1000, 9999)),
                            Forms\Components\Toggle::make('must_change')
                                ->label('يجب تغييرها عند الدخول')
                                ->default(true),
                            Forms\Components\Toggle::make('notify_user')
                                ->label('إرسال إشعار للمستخدم')
                                ->default(false),
                        ])
                        ->action(function ($record, array $data) {
                            $record->update([
                                'password' => Hash::make($data['new_password']),
                                'must_change_password' => $data['must_change'],
                            ]);
                            Notification::make()
                                ->success()
                                ->title('تم تغيير كلمة المرور')
                                ->body('كلمة المرور الجديدة: ' . $data['new_password'])
                                ->persistent()
                                ->send();
                        }),
                    Tables\Actions\Action::make('view_permissions')
                        ->label('عرض الصلاحيات')
                        ->icon('heroicon-o-shield-check')
                        ->color('info')
                        ->modalHeading(fn ($record) => "صلاحيات: {$record->name}")
                        ->modalContent(function ($record) {
                            $role = $record->role;
                            if (!$role) {
                                return view('filament.components.no-permissions');
                            }
                            $modules = $role->systemModules()->with('screens')->get();
                            return view('filament.components.user-permissions', compact('modules', 'role'));
                        })
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('إغلاق'),
                    Tables\Actions\DeleteAction::make()
                        ->label('حذف')
                        ->visible(fn ($record) => $record->role?->code !== 'super_admin' || User::where('role_id', $record->role_id)->count() > 1),
                ])
                ->icon('heroicon-o-ellipsis-vertical'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('تفعيل المحدد')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('تعطيل المحدد')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف المحدد'),
                ]),
            ])
            ->emptyStateHeading('لا يوجد مستخدمين')
            ->emptyStateDescription('ابدأ بإضافة مستخدم جديد للنظام')
            ->emptyStateIcon('heroicon-o-users')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make()
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\ImageEntry::make('avatar')
                                    ->label('')
                                    ->circular()
                                    ->size(100)
                                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&background=random&size=100'),
                                Infolists\Components\Grid::make(1)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('name')
                                            ->label('الاسم')
                                            ->size('lg')
                                            ->weight('bold'),
                                        Infolists\Components\TextEntry::make('email')
                                            ->label('البريد')
                                            ->icon('heroicon-o-envelope')
                                            ->copyable(),
                                        Infolists\Components\TextEntry::make('phone')
                                            ->label('الجوال')
                                            ->icon('heroicon-o-phone')
                                            ->placeholder('—'),
                                    ])
                                    ->columnSpan(2),
                            ]),
                    ]),
                Infolists\Components\Section::make('الدور والصلاحيات')
                    ->icon('heroicon-o-shield-check')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\TextEntry::make('role.name_ar')
                            ->label('الدور الوظيفي')
                            ->badge()
                            ->color('warning'),
                        Infolists\Components\TextEntry::make('role.level')
                            ->label('مستوى الصلاحية')
                            ->badge()
                            ->color(fn ($state) => $state >= 90 ? 'danger' : ($state >= 50 ? 'warning' : 'gray')),
                        Infolists\Components\TextEntry::make('branch.name_ar')
                            ->label('الفرع')
                            ->placeholder('غير محدد'),
                        Infolists\Components\TextEntry::make('role.systemModules.name_ar')
                            ->label('الوحدات المتاحة')
                            ->badge()
                            ->color('success')
                            ->separator(', ')
                            ->columnSpanFull(),
                    ]),
                Infolists\Components\Section::make('معلومات الحساب')
                    ->icon('heroicon-o-information-circle')
                    ->columns(4)
                    ->schema([
                        Infolists\Components\IconEntry::make('is_active')
                            ->label('الحالة')
                            ->boolean(),
                        Infolists\Components\TextEntry::make('last_login_at')
                            ->label('آخر دخول')
                            ->since()
                            ->placeholder('لم يسجل دخول'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('تاريخ الإنشاء')
                            ->date('Y-m-d H:i'),
                        Infolists\Components\TextEntry::make('language')
                            ->label('اللغة')
                            ->formatStateUsing(fn ($state) => $state === 'ar' ? 'العربية' : 'English'),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'username', 'name_ar', 'name_en', 'phone'];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', true)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
