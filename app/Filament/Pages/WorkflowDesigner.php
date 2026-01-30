<?php

namespace App\Filament\Pages;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowStep;
use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Livewire\Attributes\Url;

class WorkflowDesigner extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';
    protected static ?string $navigationLabel = 'مصمم سير العمل';
    protected static ?string $title = 'مصمم سير العمل';
    protected static bool $shouldRegisterNavigation = false;
    protected static string $view = 'filament.pages.workflow-designer';

    #[Url]
    public ?int $workflow = null;

    public ?WorkflowDefinition $workflowDefinition = null;
    public array $steps = [];
    
    // بيانات الفورم
    public ?int $editingStepId = null;
    public ?string $name = '';
    public ?string $description = '';
    public ?string $step_type = 'action';
    public ?string $assignment_type = 'role';
    public ?int $assigned_role_id = null;
    public ?int $assigned_team_id = null;
    public ?int $assigned_user_id = null;
    public ?string $dynamic_assignment = null;
    public ?string $required_permission = null;
    public bool $allow_delegation = true;
    public bool $notify_on_assignment = true;
    public ?int $escalation_hours = null;
    public ?int $escalate_to_role_id = null;
    public bool $is_final = false;
    
    public bool $showStepModal = false;

    public function mount(): void
    {
        if ($this->workflow) {
            $this->workflowDefinition = WorkflowDefinition::with('steps')->find($this->workflow);
            if ($this->workflowDefinition) {
                $this->loadSteps();
            }
        }
    }

    protected function loadSteps(): void
    {
        $this->steps = $this->workflowDefinition->steps()
            ->orderBy('step_order')
            ->get()
            ->map(function ($step) {
                return [
                    'id' => $step->id,
                    'step_order' => $step->step_order,
                    'name' => $step->name,
                    'description' => $step->description,
                    'step_type' => $step->step_type,
                    'assignment_type' => $step->assignment_type ?? 'role',
                    'assigned_role_id' => $step->assigned_role_id,
                    'assigned_team_id' => $step->assigned_team_id,
                    'assigned_user_id' => $step->assigned_user_id,
                    'dynamic_assignment' => $step->dynamic_assignment,
                    'required_permission' => $step->required_permission,
                    'allow_delegation' => $step->allow_delegation ?? true,
                    'notify_on_assignment' => $step->notify_on_assignment ?? true,
                    'escalation_hours' => $step->escalation_hours,
                    'escalate_to_role_id' => $step->escalate_to_role_id,
                    'is_final' => $step->is_final ?? false,
                    // الحقول المحسوبة للعرض
                    'assignment_description' => $step->assignment_description,
                    'role_name' => $step->assignedRole?->name_ar,
                    'team_name' => $step->assignedTeam?->name_ar,
                    'user_name' => $step->assignedUser?->name,
                ];
            })
            ->toArray();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('اسم الخطوة')
                            ->required(),
                        Forms\Components\Select::make('step_type')
                            ->label('نوع الخطوة')
                            ->options([
                                'action' => 'إجراء',
                                'approval' => 'موافقة',
                                'review' => 'مراجعة',
                                'notification' => 'إشعار',
                            ])
                            ->default('action'),
                    ]),

                Forms\Components\Textarea::make('description')
                    ->label('الوصف')
                    ->rows(2),

                Forms\Components\Section::make('التعيين')
                    ->schema([
                        Forms\Components\Select::make('assignment_type')
                            ->label('طريقة التعيين')
                            ->options(WorkflowStep::ASSIGNMENT_TYPES)
                            ->default('role')
                            ->live()
                            ->required(),

                        Forms\Components\Select::make('assigned_role_id')
                            ->label('الدور')
                            ->options(Role::pluck('name_ar', 'id'))
                            ->searchable()
                            ->visible(fn ($get) => $get('assignment_type') === 'role'),

                        Forms\Components\Select::make('assigned_team_id')
                            ->label('الفريق')
                            ->options(Team::where('is_active', true)->pluck('name_ar', 'id'))
                            ->searchable()
                            ->visible(fn ($get) => $get('assignment_type') === 'team'),

                        Forms\Components\Select::make('assigned_user_id')
                            ->label('المستخدم')
                            ->options(User::where('is_active', true)->pluck('name', 'id'))
                            ->searchable()
                            ->visible(fn ($get) => $get('assignment_type') === 'user'),

                        Forms\Components\Select::make('dynamic_assignment')
                            ->label('التعيين الديناميكي')
                            ->options(WorkflowStep::DYNAMIC_ASSIGNMENTS)
                            ->visible(fn ($get) => $get('assignment_type') === 'dynamic'),

                        Forms\Components\Select::make('required_permission')
                            ->label('الصلاحية المطلوبة (اختياري)')
                            ->options(Permission::pluck('name_ar', 'code'))
                            ->searchable()
                            ->helperText('صلاحية إضافية يجب أن يمتلكها المستخدم'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('الخيارات')
                    ->schema([
                        Forms\Components\Toggle::make('allow_delegation')
                            ->label('السماح بالتفويض')
                            ->default(true),
                        Forms\Components\Toggle::make('notify_on_assignment')
                            ->label('إشعار عند التعيين')
                            ->default(true),
                        Forms\Components\Toggle::make('is_final')
                            ->label('خطوة نهائية'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('التصعيد')
                    ->schema([
                        Forms\Components\TextInput::make('escalation_hours')
                            ->label('ساعات قبل التصعيد')
                            ->numeric()
                            ->helperText('اتركه فارغاً لعدم التصعيد'),
                        Forms\Components\Select::make('escalate_to_role_id')
                            ->label('تصعيد إلى')
                            ->options(Role::pluck('name_ar', 'id'))
                            ->searchable(),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }

    public function addStep(): void
    {
        $this->resetFormFields();
        $this->showStepModal = true;
        $this->dispatch('open-modal', id: 'step-modal');
    }
    
    protected function resetFormFields(): void
    {
        $this->editingStepId = null;
        $this->name = '';
        $this->description = '';
        $this->step_type = 'action';
        $this->assignment_type = 'role';
        $this->assigned_role_id = null;
        $this->assigned_team_id = null;
        $this->assigned_user_id = null;
        $this->dynamic_assignment = null;
        $this->required_permission = null;
        $this->allow_delegation = true;
        $this->notify_on_assignment = true;
        $this->escalation_hours = null;
        $this->escalate_to_role_id = null;
        $this->is_final = false;
    }

    public function editStep(int $index): void
    {
        $step = $this->steps[$index];
        
        $this->editingStepId = $step['id'];
        $this->name = $step['name'] ?? '';
        $this->description = $step['description'] ?? '';
        $this->step_type = $step['step_type'] ?? 'action';
        $this->assignment_type = $step['assignment_type'] ?? 'role';
        $this->assigned_role_id = $step['assigned_role_id'];
        $this->assigned_team_id = $step['assigned_team_id'];
        $this->assigned_user_id = $step['assigned_user_id'];
        $this->dynamic_assignment = $step['dynamic_assignment'];
        $this->required_permission = $step['required_permission'];
        $this->allow_delegation = $step['allow_delegation'] ?? true;
        $this->notify_on_assignment = $step['notify_on_assignment'] ?? true;
        $this->escalation_hours = $step['escalation_hours'];
        $this->escalate_to_role_id = $step['escalate_to_role_id'];
        $this->is_final = $step['is_final'] ?? false;
        
        $this->showStepModal = true;
        $this->dispatch('open-modal', id: 'step-modal');
    }

    public function saveStep(): void
    {
        // التحقق من الاسم
        if (empty($this->name)) {
            Notification::make()
                ->title('يرجى إدخال اسم الخطوة')
                ->danger()
                ->send();
            return;
        }

        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'step_type' => $this->step_type,
            'assignment_type' => $this->assignment_type,
            'assigned_role_id' => $this->assignment_type === 'role' ? $this->assigned_role_id : null,
            'assigned_team_id' => $this->assignment_type === 'team' ? $this->assigned_team_id : null,
            'assigned_user_id' => $this->assignment_type === 'user' ? $this->assigned_user_id : null,
            'dynamic_assignment' => $this->assignment_type === 'dynamic' ? $this->dynamic_assignment : null,
            'required_permission' => $this->required_permission,
            'allow_delegation' => $this->allow_delegation,
            'notify_on_assignment' => $this->notify_on_assignment,
            'escalation_hours' => $this->escalation_hours,
            'escalate_to_role_id' => $this->escalate_to_role_id,
            'is_final' => $this->is_final,
        ];

        if ($this->editingStepId) {
            // تحديث خطوة موجودة
            WorkflowStep::where('id', $this->editingStepId)->update($data);
        } else {
            // إنشاء خطوة جديدة
            $maxOrder = WorkflowStep::where('workflow_definition_id', $this->workflowDefinition->id)
                ->max('step_order') ?? 0;
            
            $data['workflow_definition_id'] = $this->workflowDefinition->id;
            $data['step_order'] = $maxOrder + 1;
            
            WorkflowStep::create($data);
        }

        $this->loadSteps();
        $this->closeModal();

        Notification::make()
            ->title('تم حفظ الخطوة بنجاح')
            ->success()
            ->send();
    }

    public function deleteStep(int $index): void
    {
        $step = $this->steps[$index];
        if ($step['id']) {
            WorkflowStep::destroy($step['id']);
        }
        
        $this->loadSteps();
        $this->reorderSteps();

        Notification::make()
            ->title('تم حذف الخطوة')
            ->success()
            ->send();
    }

    public function moveStepUp(int $index): void
    {
        if ($index <= 0) return;

        $steps = $this->steps;
        $temp = $steps[$index];
        $steps[$index] = $steps[$index - 1];
        $steps[$index - 1] = $temp;
        
        $this->steps = $steps;
        $this->reorderSteps();
    }

    public function moveStepDown(int $index): void
    {
        if ($index >= count($this->steps) - 1) return;

        $steps = $this->steps;
        $temp = $steps[$index];
        $steps[$index] = $steps[$index + 1];
        $steps[$index + 1] = $temp;
        
        $this->steps = $steps;
        $this->reorderSteps();
    }

    protected function reorderSteps(): void
    {
        foreach ($this->steps as $index => $step) {
            if ($step['id']) {
                WorkflowStep::where('id', $step['id'])->update(['step_order' => $index + 1]);
            }
        }
        $this->loadSteps();
    }

    public function closeModal(): void
    {
        $this->showStepModal = false;
        $this->resetFormFields();
        $this->dispatch('close-modal', id: 'step-modal');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('العودة')
                ->icon('heroicon-o-arrow-right')
                ->color('gray')
                ->url(route('filament.admin.pages.access-management', ['activeTab' => 'workflows'])),
        ];
    }

    public function getAssignmentIcon(string $type): string
    {
        return match($type) {
            'role' => 'heroicon-o-shield-check',
            'team' => 'heroicon-o-user-group',
            'user' => 'heroicon-o-user',
            'dynamic' => 'heroicon-o-arrows-right-left',
            default => 'heroicon-o-question-mark-circle',
        };
    }

    public function getAssignmentColor(string $type): string
    {
        return match($type) {
            'role' => 'warning',
            'team' => 'success',
            'user' => 'info',
            'dynamic' => 'primary',
            default => 'gray',
        };
    }
}
