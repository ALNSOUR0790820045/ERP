<?php

namespace App\Filament\Pages\Reports;

use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use App\Models\Payroll;
use App\Models\Employee;
use App\Models\IncomeTaxBracket;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class IncomeTaxReport extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';
    
    protected static ?string $navigationLabel = 'تقرير ضريبة الدخل';
    
    protected static ?string $title = 'تقرير ضريبة الدخل للموظفين';
    
    protected static ?string $navigationGroup = 'التقارير';
    
    protected static ?int $navigationSort = 4;
    
    protected static string $view = 'filament.pages.reports.income-tax-report';
    
    public ?array $data = [];
    
    public Collection $reportData;
    
    public Collection $taxBrackets;
    
    public bool $showReport = false;
    
    public function mount(): void
    {
        $this->form->fill([
            'year' => now()->year,
            'taxpayer_type' => 'individual',
        ]);
        $this->reportData = collect();
        $this->taxBrackets = collect();
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معايير التقرير')
                    ->schema([
                        Forms\Components\Select::make('year')
                            ->label('السنة المالية')
                            ->options(array_combine(
                                range(now()->year - 2, now()->year),
                                range(now()->year - 2, now()->year)
                            ))
                            ->required(),
                        
                        Forms\Components\Select::make('taxpayer_type')
                            ->label('نوع المكلف')
                            ->options([
                                'individual' => 'فرد',
                                'family' => 'عائل',
                            ])
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
        $year = $this->data['year'];
        $taxpayerType = $this->data['taxpayer_type'];
        
        // الحصول على شرائح الضريبة
        $this->taxBrackets = IncomeTaxBracket::where('year', $year)
            ->where('taxpayer_type', $taxpayerType)
            ->where('is_active', true)
            ->orderBy('min_amount')
            ->get();
        
        // جمع الرواتب السنوية
        $query = Employee::query()
            ->with(['department', 'payrolls' => function ($q) use ($year) {
                $q->whereYear('payroll_date', $year);
            }])
            ->where('status', 'active');
        
        if (!empty($this->data['department_id'])) {
            $query->where('department_id', $this->data['department_id']);
        }
        
        $this->reportData = $query->get()->map(function ($employee) {
            $annualIncome = $employee->payrolls->sum('gross_salary');
            $taxCalculation = $this->calculateTax($annualIncome);
            
            return [
                'employee_number' => $employee->employee_number,
                'employee_name' => $employee->full_name,
                'department' => $employee->department?->name ?? '-',
                'national_id' => $employee->national_id,
                'annual_income' => $annualIncome,
                'taxable_income' => $taxCalculation['taxable_income'],
                'tax_amount' => $taxCalculation['tax_amount'],
                'effective_rate' => $taxCalculation['effective_rate'],
                'tax_bracket' => $taxCalculation['bracket'],
            ];
        })->filter(fn ($item) => $item['annual_income'] > 0);
        
        $this->showReport = true;
    }
    
    protected function calculateTax(float $annualIncome): array
    {
        $taxAmount = 0;
        $remainingIncome = $annualIncome;
        $bracket = 'معفي';
        
        foreach ($this->taxBrackets as $taxBracket) {
            if ($annualIncome > $taxBracket->min_amount) {
                $taxableInBracket = min(
                    $remainingIncome,
                    ($taxBracket->max_amount ?? PHP_INT_MAX) - $taxBracket->min_amount
                );
                
                if ($taxableInBracket > 0) {
                    $taxAmount += $taxableInBracket * ($taxBracket->rate / 100);
                    $remainingIncome -= $taxableInBracket;
                    $bracket = $taxBracket->rate . '%';
                }
            }
        }
        
        $exemption = $this->taxBrackets->first()?->min_amount ?? 0;
        $taxableIncome = max(0, $annualIncome - $exemption);
        
        return [
            'tax_amount' => round($taxAmount, 2),
            'taxable_income' => $taxableIncome,
            'effective_rate' => $annualIncome > 0 
                ? round(($taxAmount / $annualIncome) * 100, 2) . '%' 
                : '0%',
            'bracket' => $bracket,
        ];
    }
    
    public function getTotals(): array
    {
        return [
            'total_income' => $this->reportData->sum('annual_income'),
            'total_taxable' => $this->reportData->sum('taxable_income'),
            'total_tax' => $this->reportData->sum('tax_amount'),
            'employee_count' => $this->reportData->count(),
        ];
    }
    
    public function getTaxBracketsSummary(): Collection
    {
        return $this->taxBrackets->map(function ($bracket) {
            return [
                'min' => number_format($bracket->min_amount),
                'max' => $bracket->max_amount ? number_format($bracket->max_amount) : 'وما فوق',
                'rate' => $bracket->rate . '%',
            ];
        });
    }
}
