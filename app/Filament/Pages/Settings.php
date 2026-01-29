<?php

namespace App\Filament\Pages;

use App\Models\Company;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class Settings extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = 'إعدادات النظام';
    protected static ?string $navigationLabel = 'إعدادات النظام';
    protected static ?string $title = 'إعدادات النظام';
    protected static ?int $navigationSort = 100;
    protected static string $view = 'filament.pages.settings';

    public ?array $data = [];
    public $company;

    public function mount(): void
    {
        $this->company = Company::first();
        
        $this->form->fill([
            'company_name_ar' => $this->company?->name_ar ?? '',
            'company_name_en' => $this->company?->name_en ?? '',
            'legal_name' => $this->company?->legal_name ?? '',
            'registration_number' => $this->company?->registration_number ?? '',
            'tax_number' => $this->company?->tax_number ?? '',
            'vat_number' => $this->company?->vat_number ?? '',
            'phone' => $this->company?->phone ?? '',
            'email' => $this->company?->email ?? '',
            'website' => $this->company?->website ?? '',
            'address' => $this->company?->address ?? '',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('إعدادات')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('معلومات الشركة')
                            ->icon('heroicon-o-building-office-2')
                            ->schema([
                                Forms\Components\Section::make('البيانات الأساسية')
                                    ->schema([
                                        Forms\Components\TextInput::make('company_name_ar')
                                            ->label('اسم الشركة (عربي)')
                                            ->required(),
                                        Forms\Components\TextInput::make('company_name_en')
                                            ->label('اسم الشركة (إنجليزي)'),
                                        Forms\Components\TextInput::make('legal_name')
                                            ->label('الاسم القانوني')
                                            ->required(),
                                    ])->columns(3),
                                
                                Forms\Components\Section::make('الأرقام الرسمية')
                                    ->schema([
                                        Forms\Components\TextInput::make('registration_number')
                                            ->label('رقم السجل التجاري')
                                            ->required(),
                                        Forms\Components\TextInput::make('tax_number')
                                            ->label('الرقم الضريبي'),
                                        Forms\Components\TextInput::make('vat_number')
                                            ->label('رقم ضريبة المبيعات'),
                                    ])->columns(3),
                            ]),
                        
                        Forms\Components\Tabs\Tab::make('معلومات الاتصال')
                            ->icon('heroicon-o-phone')
                            ->schema([
                                Forms\Components\Section::make('وسائل الاتصال')
                                    ->schema([
                                        Forms\Components\TextInput::make('phone')
                                            ->label('الهاتف')
                                            ->tel(),
                                        Forms\Components\TextInput::make('email')
                                            ->label('البريد الإلكتروني')
                                            ->email(),
                                        Forms\Components\TextInput::make('website')
                                            ->label('الموقع الإلكتروني')
                                            ->url(),
                                        Forms\Components\Textarea::make('address')
                                            ->label('العنوان')
                                            ->rows(3)
                                            ->columnSpanFull(),
                                    ])->columns(3),
                            ]),
                        
                        Forms\Components\Tabs\Tab::make('إعدادات العطاءات')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Forms\Components\Section::make('إعدادات التنبيهات')
                                    ->schema([
                                        Forms\Components\Toggle::make('tender_deadline_alerts')
                                            ->label('تفعيل تنبيهات مواعيد الإغلاق')
                                            ->default(true),
                                        Forms\Components\TextInput::make('tender_alert_days')
                                            ->label('أيام التنبيه قبل الموعد')
                                            ->numeric()
                                            ->default(7),
                                        Forms\Components\Toggle::make('bond_expiry_alerts')
                                            ->label('تفعيل تنبيهات انتهاء الكفالات')
                                            ->default(true),
                                    ])->columns(3),
                            ]),
                        
                        Forms\Components\Tabs\Tab::make('إعدادات النظام')
                            ->icon('heroicon-o-cog')
                            ->schema([
                                Forms\Components\Section::make('الإعدادات العامة')
                                    ->schema([
                                        Forms\Components\Select::make('timezone')
                                            ->label('المنطقة الزمنية')
                                            ->options([
                                                'Asia/Amman' => 'عمان (الأردن)',
                                                'Asia/Riyadh' => 'الرياض (السعودية)',
                                                'Asia/Dubai' => 'دبي (الإمارات)',
                                                'Africa/Cairo' => 'القاهرة (مصر)',
                                            ])
                                            ->default('Asia/Amman'),
                                        Forms\Components\Select::make('date_format')
                                            ->label('صيغة التاريخ')
                                            ->options([
                                                'Y-m-d' => '2026-01-29',
                                                'd/m/Y' => '29/01/2026',
                                                'd-m-Y' => '29-01-2026',
                                            ])
                                            ->default('Y-m-d'),
                                        Forms\Components\Select::make('currency_display')
                                            ->label('عرض العملة')
                                            ->options([
                                                'symbol' => 'رمز (د.أ)',
                                                'code' => 'كود (JOD)',
                                                'name' => 'اسم (دينار أردني)',
                                            ])
                                            ->default('symbol'),
                                    ])->columns(3),
                            ]),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        
        if ($this->company) {
            $this->company->update([
                'name_ar' => $data['company_name_ar'],
                'name_en' => $data['company_name_en'],
                'legal_name' => $data['legal_name'],
                'registration_number' => $data['registration_number'],
                'tax_number' => $data['tax_number'],
                'vat_number' => $data['vat_number'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'website' => $data['website'],
                'address' => $data['address'],
            ]);
        }
        
        // Clear cache
        Cache::forget('company_settings');
        
        Notification::make()
            ->title('تم الحفظ')
            ->body('تم حفظ الإعدادات بنجاح')
            ->success()
            ->send();
    }
}
