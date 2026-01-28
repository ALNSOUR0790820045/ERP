<?php

namespace App\Filament\Pages;

use App\Models\Project;
use App\Models\Employee;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Contract;
use Filament\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Actions\Action;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdvancedReportsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';

    protected static string $view = 'filament.pages.advanced-reports-page';

    protected static ?string $navigationGroup = 'التقارير';

    protected static ?string $navigationLabel = 'التقارير المتقدمة';

    protected static ?string $title = 'التقارير المتقدمة';

    protected static ?int $navigationSort = 5;

    public ?string $reportType = 'summary';
    public ?string $dateFrom = null;
    public ?string $dateTo = null;
    public ?int $projectId = null;
    public ?int $departmentId = null;

    public function mount(): void
    {
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->endOfMonth()->format('Y-m-d');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('reportType')
                    ->label('نوع التقرير')
                    ->options([
                        'summary' => 'ملخص عام',
                        'projects' => 'تقرير المشاريع',
                        'employees' => 'تقرير الموظفين',
                        'financial' => 'تقرير مالي',
                        'performance' => 'تقرير الأداء',
                        'comparison' => 'تقرير مقارنة',
                    ])
                    ->default('summary')
                    ->live(),

                DatePicker::make('dateFrom')
                    ->label('من تاريخ')
                    ->default(now()->startOfMonth()),

                DatePicker::make('dateTo')
                    ->label('إلى تاريخ')
                    ->default(now()->endOfMonth()),

                Select::make('projectId')
                    ->label('المشروع')
                    ->options(fn () => Project::pluck('name', 'id'))
                    ->searchable()
                    ->visible(fn ($get) => in_array($get('reportType'), ['projects', 'performance'])),
            ])
            ->columns(4);
    }

    public function getReportData(): array
    {
        return match($this->reportType) {
            'summary' => $this->getSummaryReport(),
            'projects' => $this->getProjectsReport(),
            'employees' => $this->getEmployeesReport(),
            'financial' => $this->getFinancialReport(),
            'performance' => $this->getPerformanceReport(),
            'comparison' => $this->getComparisonReport(),
            default => [],
        };
    }

    protected function getSummaryReport(): array
    {
        return [
            'title' => 'ملخص عام',
            'stats' => [
                [
                    'label' => 'إجمالي المشاريع',
                    'value' => Project::count(),
                    'icon' => 'heroicon-o-briefcase',
                    'color' => 'primary',
                ],
                [
                    'label' => 'المشاريع النشطة',
                    'value' => Project::where('status', 'in_progress')->count(),
                    'icon' => 'heroicon-o-play',
                    'color' => 'success',
                ],
                [
                    'label' => 'إجمالي الموظفين',
                    'value' => Employee::count(),
                    'icon' => 'heroicon-o-users',
                    'color' => 'info',
                ],
                [
                    'label' => 'العملاء',
                    'value' => Customer::count(),
                    'icon' => 'heroicon-o-user-group',
                    'color' => 'warning',
                ],
            ],
            'charts' => [
                'projects_by_status' => $this->getProjectsByStatus(),
                'monthly_revenue' => $this->getMonthlyRevenue(),
            ],
        ];
    }

    protected function getProjectsReport(): array
    {
        $projects = Project::query()
            ->when($this->projectId, fn ($q) => $q->where('id', $this->projectId))
            ->withCount(['tasks', 'contracts'])
            ->get();

        return [
            'title' => 'تقرير المشاريع',
            'data' => $projects->map(fn ($project) => [
                'id' => $project->id,
                'name' => $project->name,
                'status' => $project->status,
                'start_date' => $project->start_date,
                'end_date' => $project->end_date,
                'budget' => $project->budget ?? 0,
                'spent' => $project->actual_cost ?? 0,
                'progress' => $project->progress ?? 0,
                'tasks_count' => $project->tasks_count,
            ])->toArray(),
            'totals' => [
                'total_projects' => $projects->count(),
                'total_budget' => $projects->sum('budget'),
                'total_spent' => $projects->sum('actual_cost'),
                'avg_progress' => round($projects->avg('progress') ?? 0, 2),
            ],
        ];
    }

    protected function getEmployeesReport(): array
    {
        $employees = Employee::query()
            ->with(['department', 'jobTitle'])
            ->get();

        $byDepartment = $employees->groupBy('department.name')
            ->map(fn ($group) => $group->count())
            ->toArray();

        return [
            'title' => 'تقرير الموظفين',
            'data' => $employees->map(fn ($emp) => [
                'id' => $emp->id,
                'name' => $emp->full_name ?? $emp->name,
                'department' => $emp->department?->name,
                'job_title' => $emp->jobTitle?->name,
                'hire_date' => $emp->hire_date,
                'status' => $emp->status ?? 'active',
            ])->toArray(),
            'by_department' => $byDepartment,
            'totals' => [
                'total_employees' => $employees->count(),
                'active' => $employees->where('status', 'active')->count(),
            ],
        ];
    }

    protected function getFinancialReport(): array
    {
        $from = $this->dateFrom ? Carbon::parse($this->dateFrom) : now()->startOfMonth();
        $to = $this->dateTo ? Carbon::parse($this->dateTo) : now()->endOfMonth();

        $invoices = Invoice::whereBetween('invoice_date', [$from, $to])->get();

        return [
            'title' => 'التقرير المالي',
            'period' => "{$from->format('Y-m-d')} إلى {$to->format('Y-m-d')}",
            'revenue' => [
                'total_invoiced' => $invoices->sum('total_amount'),
                'total_paid' => $invoices->where('status', 'paid')->sum('total_amount'),
                'total_pending' => $invoices->whereIn('status', ['sent', 'pending'])->sum('total_amount'),
                'total_overdue' => $invoices->where('status', 'overdue')->sum('total_amount'),
            ],
            'invoice_count' => [
                'total' => $invoices->count(),
                'paid' => $invoices->where('status', 'paid')->count(),
                'pending' => $invoices->whereIn('status', ['sent', 'pending'])->count(),
            ],
        ];
    }

    protected function getPerformanceReport(): array
    {
        $project = $this->projectId ? Project::find($this->projectId) : null;

        if (!$project) {
            return [
                'title' => 'تقرير الأداء',
                'message' => 'يرجى اختيار مشروع',
            ];
        }

        return [
            'title' => "تقرير أداء: {$project->name}",
            'project' => [
                'name' => $project->name,
                'status' => $project->status,
                'progress' => $project->progress ?? 0,
                'budget' => $project->budget ?? 0,
                'actual_cost' => $project->actual_cost ?? 0,
            ],
            'variance' => [
                'cost_variance' => ($project->budget ?? 0) - ($project->actual_cost ?? 0),
                'cost_variance_percent' => $project->budget > 0 
                    ? round((($project->budget - ($project->actual_cost ?? 0)) / $project->budget) * 100, 2)
                    : 0,
            ],
        ];
    }

    protected function getComparisonReport(): array
    {
        $from = $this->dateFrom ? Carbon::parse($this->dateFrom) : now()->startOfMonth();
        $to = $this->dateTo ? Carbon::parse($this->dateTo) : now()->endOfMonth();

        $previousFrom = $from->copy()->subMonth();
        $previousTo = $to->copy()->subMonth();

        // Current period
        $currentInvoices = Invoice::whereBetween('invoice_date', [$from, $to])->sum('total_amount');
        $currentProjects = Project::whereBetween('created_at', [$from, $to])->count();

        // Previous period
        $previousInvoices = Invoice::whereBetween('invoice_date', [$previousFrom, $previousTo])->sum('total_amount');
        $previousProjects = Project::whereBetween('created_at', [$previousFrom, $previousTo])->count();

        return [
            'title' => 'تقرير المقارنة',
            'current_period' => "{$from->format('Y-m-d')} - {$to->format('Y-m-d')}",
            'previous_period' => "{$previousFrom->format('Y-m-d')} - {$previousTo->format('Y-m-d')}",
            'comparison' => [
                'revenue' => [
                    'current' => $currentInvoices,
                    'previous' => $previousInvoices,
                    'change' => $previousInvoices > 0 
                        ? round((($currentInvoices - $previousInvoices) / $previousInvoices) * 100, 2)
                        : 0,
                ],
                'projects' => [
                    'current' => $currentProjects,
                    'previous' => $previousProjects,
                    'change' => $previousProjects > 0 
                        ? round((($currentProjects - $previousProjects) / $previousProjects) * 100, 2)
                        : 0,
                ],
            ],
        ];
    }

    protected function getProjectsByStatus(): array
    {
        return Project::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    protected function getMonthlyRevenue(): array
    {
        return Invoice::select(
                DB::raw("strftime('%Y-%m', invoice_date) as month"),
                DB::raw('sum(total_amount) as total')
            )
            ->whereYear('invoice_date', now()->year)
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month')
            ->toArray();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_pdf')
                ->label('تصدير PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger'),

            Action::make('export_excel')
                ->label('تصدير Excel')
                ->icon('heroicon-o-table-cells')
                ->color('success'),

            Action::make('print')
                ->label('طباعة')
                ->icon('heroicon-o-printer')
                ->color('gray'),
        ];
    }
}
