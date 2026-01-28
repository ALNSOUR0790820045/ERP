<?php

namespace App\Filament\Pages\Reports;

use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use App\Models\Payroll;
use App\Models\Employee;
use App\Models\SocialSecuritySetting;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class SocialSecurityReport extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    
    protected static ?string $navigationLabel = 'تقرير الضمان الاجتماعي';
    
    protected static ?string $title = 'تقرير اشتراكات الضمان الاجتماعي';
    
    protected static ?string $navigationGroup = 'التقارير';
    
    protected static ?int $navigationSort = 3;
    
    protected static string $view = 'filament.pages.reports.social-security-report';
    
    public ?array $data = [];
    
    public Collection $reportData;
    
    public bool $showReport = false;
    
    public ?SocialSecuritySetting $currentSettings = null;
    
    public function mount(): void
    {
        $this->form->fill([
            'month' => now()->month,
            'year' => now()->year,
        ]);
        $this->reportData = collect();
        $this->currentSettings = SocialSecuritySetting::where('year', now()->year)
            ->where('is_active', true)
            ->first();
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معايير التقرير')
                    ->schema([
                        Forms\Components\Select::make('month')
                            ->label('الشهر')
                            ->options([
                                1 => 'يناير', 2 => 'فبراير', 3 => 'مارس',
                                4 => 'أبريل', 5 => 'مايو', 6 => 'يونيو',
                                7 => 'يوليو', 8 => 'أغسطس', 9 => 'سبتمبر',
                                10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
                            ])
                            ->required(),
                        
                        Forms\Components\Select::make('year')
                            ->label('السنة')
                            ->options(array_combine(
                                range(now()->year - 2, now()->year),
                                range(now()->year - 2, now()->year)
                            ))
                            ->required(),
                        
                        Forms\Components\Select::make('department_id')
                            ->label('القسم')
                            ->relationship('department', 'name')
                            ->placeholder('جميع الأقسام'),
                    ])
                    ->columns(3),
            ])
            ->statePath('data');
    }
    
    public function generateReport(): void
    {
        $month = $this->data['month'];
        $year = $this->data['year'];
        
        $this->currentSettings = SocialSecuritySetting::where('year', $year)
            ->where('is_active', true)
            ->first();
        
        $query = Payroll::query()
            ->with(['employee', 'employee.department'])
            ->whereMonth('payroll_date', $month)
            ->whereYear('payroll_date', $year);
        
        if (!empty($this->data['department_id'])) {
            $query->whereHas('employee', function ($q) {
                $q->where('department_id', $this->data['department_id']);
            });
        }
        
        $employerRate = $this->currentSettings?->employer_rate ?? 14.25;
        $employeeRate = $this->currentSettings?->employee_rate ?? 7.5;
        
        $this->reportData = $query->get()->map(function ($payroll) use ($employerRate, $employeeRate) {
            $grossSalary = $payroll->gross_salary ?? $payroll->basic_salary;
            $employeeContribution = $grossSalary * ($employeeRate / 100);
            $employerContribution = $grossSalary * ($employerRate / 100);
            
            return [
                'employee_number' => $payroll->employee?->employee_number ?? '-',
                'employee_name' => $payroll->employee?->full_name ?? '-',
                'department' => $payroll->employee?->department?->name ?? '-',
                'national_id' => $payroll->employee?->national_id ?? '-',
                'gross_salary' => $grossSalary,
                'employee_contribution' => $employeeContribution,
                'employer_contribution' => $employerContribution,
                'total_contribution' => $employeeContribution + $employerContribution,
            ];
        });
        
        $this->showReport = true;
    }
    
    public function getTotals(): array
    {
        return [
            'total_gross' => $this->reportData->sum('gross_salary'),
            'total_employee' => $this->reportData->sum('employee_contribution'),
            'total_employer' => $this->reportData->sum('employer_contribution'),
            'total_combined' => $this->reportData->sum('total_contribution'),
            'employee_count' => $this->reportData->count(),
        ];
    }
    
    public function getSettings(): array
    {
        return [
            'employer_rate' => $this->currentSettings?->employer_rate ?? 14.25,
            'employee_rate' => $this->currentSettings?->employee_rate ?? 7.5,
            'minimum_wage' => $this->currentSettings?->minimum_wage ?? 260,
        ];
    }
}
