<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_definition_id',
        'step_order',
        'name',
        'description',
        'step_type',
        'approver_type',
        'approver_id',
        'approver_role',
        'approval_type',
        'time_limit_hours',
        'escalation_step_id',
        'on_approve_step_id',
        'on_reject_step_id',
        'conditions',
        'is_final',
        // الحقول الجديدة للتعيينات
        'assignment_type',
        'assigned_role_id',
        'assigned_team_id',
        'assigned_user_id',
        'dynamic_assignment',
        'required_permission',
        'allow_delegation',
        'auto_assign_on_create',
        'notify_on_assignment',
        'escalation_hours',
        'escalate_to_role_id',
    ];

    protected $casts = [
        'conditions' => 'array',
        'is_final' => 'boolean',
        'allow_delegation' => 'boolean',
        'auto_assign_on_create' => 'boolean',
        'notify_on_assignment' => 'boolean',
    ];

    // أنواع التعيين
    const ASSIGNMENT_TYPE_ROLE = 'role';
    const ASSIGNMENT_TYPE_TEAM = 'team';
    const ASSIGNMENT_TYPE_USER = 'user';
    const ASSIGNMENT_TYPE_DYNAMIC = 'dynamic';

    const ASSIGNMENT_TYPES = [
        'role' => 'دور/صلاحية',
        'team' => 'فريق عمل',
        'user' => 'مستخدم محدد',
        'dynamic' => 'تعيين ديناميكي',
    ];

    const DYNAMIC_ASSIGNMENTS = [
        'direct_manager' => 'المدير المباشر',
        'department_head' => 'رئيس القسم',
        'branch_manager' => 'مدير الفرع',
        'creator' => 'منشئ الطلب',
    ];

    // العلاقات
    public function workflowDefinition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function assignedRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'assigned_role_id');
    }

    public function assignedTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'assigned_team_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function escalateToRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'escalate_to_role_id');
    }

    public function escalationStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'escalation_step_id');
    }

    public function onApproveStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'on_approve_step_id');
    }

    public function onRejectStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'on_reject_step_id');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(WorkflowAction::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(WorkflowAssignment::class);
    }

    // الثوابت القديمة للتوافق
    public const APPROVER_TYPES = [
        'user' => 'مستخدم محدد',
        'role' => 'دور/صلاحية',
        'manager' => 'المدير المباشر',
        'department_head' => 'رئيس القسم',
    ];

    public const APPROVAL_TYPES = [
        'single' => 'موافقة واحدة',
        'all' => 'جميع المعتمدين',
        'majority' => 'الأغلبية',
    ];

    /**
     * الحصول على المستخدمين المؤهلين لهذه الخطوة
     */
    public function getEligibleUsers($entity = null): \Illuminate\Support\Collection
    {
        switch ($this->assignment_type) {
            case self::ASSIGNMENT_TYPE_ROLE:
                if ($this->assigned_role_id) {
                    return User::where('role_id', $this->assigned_role_id)
                        ->where('is_active', true)
                        ->get();
                }
                return collect();

            case self::ASSIGNMENT_TYPE_TEAM:
                if ($this->assigned_team_id) {
                    $team = Team::find($this->assigned_team_id);
                    return $team ? $team->activeMembers : collect();
                }
                return collect();

            case self::ASSIGNMENT_TYPE_USER:
                if ($this->assigned_user_id) {
                    $user = User::find($this->assigned_user_id);
                    return $user ? collect([$user]) : collect();
                }
                return collect();

            case self::ASSIGNMENT_TYPE_DYNAMIC:
                return $this->resolveDynamicAssignment($entity);

            default:
                // Fallback للنظام القديم
                return $this->getApprovers($entity);
        }
    }

    /**
     * حل التعيين الديناميكي
     */
    protected function resolveDynamicAssignment($entity): \Illuminate\Support\Collection
    {
        if (!$entity) {
            return collect();
        }

        switch ($this->dynamic_assignment) {
            case 'direct_manager':
                if (method_exists($entity, 'getManager')) {
                    $manager = $entity->getManager();
                    return $manager ? collect([$manager]) : collect();
                }
                // إذا كان الكيان له created_by
                if (isset($entity->created_by)) {
                    $creator = User::find($entity->created_by);
                    if ($creator && $creator->manager_id) {
                        $manager = User::find($creator->manager_id);
                        return $manager ? collect([$manager]) : collect();
                    }
                }
                return collect();

            case 'department_head':
                if (method_exists($entity, 'getDepartmentHead')) {
                    $head = $entity->getDepartmentHead();
                    return $head ? collect([$head]) : collect();
                }
                return collect();

            case 'branch_manager':
                if (isset($entity->branch_id)) {
                    $branch = Branch::find($entity->branch_id);
                    if ($branch && $branch->manager_id) {
                        $manager = User::find($branch->manager_id);
                        return $manager ? collect([$manager]) : collect();
                    }
                }
                return collect();

            case 'creator':
                if (isset($entity->created_by)) {
                    $creator = User::find($entity->created_by);
                    return $creator ? collect([$creator]) : collect();
                }
                return collect();

            default:
                return collect();
        }
    }

    /**
     * الحصول على المعتمدين لهذه الخطوة (للتوافق مع النظام القديم)
     */
    public function getApprovers($entity = null): \Illuminate\Support\Collection
    {
        switch ($this->approver_type) {
            case 'user':
                return collect([$this->approver]);
                
            case 'role':
                return User::where('role_id', function($query) {
                    $query->select('id')
                        ->from('roles')
                        ->where('code', $this->approver_role);
                })->get();
                
            case 'manager':
                if ($entity && method_exists($entity, 'getManager')) {
                    return collect([$entity->getManager()]);
                }
                return collect();
                
            case 'department_head':
                if ($entity && method_exists($entity, 'getDepartmentHead')) {
                    return collect([$entity->getDepartmentHead()]);
                }
                return collect();
                
            default:
                return collect();
        }
    }

    /**
     * التحقق من أن المستخدم مؤهل لهذه الخطوة
     */
    public function isUserEligible(User $user, $entity = null): bool
    {
        // التحقق من الصلاحية المطلوبة
        if ($this->required_permission && !$user->hasPermission($this->required_permission)) {
            return false;
        }

        $eligibleUsers = $this->getEligibleUsers($entity);
        return $eligibleUsers->contains('id', $user->id);
    }

    /**
     * الحصول على وصف التعيين
     */
    public function getAssignmentDescriptionAttribute(): string
    {
        switch ($this->assignment_type) {
            case self::ASSIGNMENT_TYPE_ROLE:
                return $this->assignedRole?->name_ar ?? 'دور غير محدد';
            case self::ASSIGNMENT_TYPE_TEAM:
                return $this->assignedTeam?->name_ar ?? 'فريق غير محدد';
            case self::ASSIGNMENT_TYPE_USER:
                return $this->assignedUser?->name ?? 'مستخدم غير محدد';
            case self::ASSIGNMENT_TYPE_DYNAMIC:
                return self::DYNAMIC_ASSIGNMENTS[$this->dynamic_assignment] ?? 'ديناميكي';
            default:
                return 'غير محدد';
        }
    }
}
