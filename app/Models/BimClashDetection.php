<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BimClashDetection extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'bim_project_id',
        'element1_id',
        'element2_id',
        'clash_type', // hard, soft, clearance, duplicate
        'severity', // critical, major, minor, informational
        'status', // new, active, approved, resolved, ignored
        'resolution_type', // modify_element1, modify_element2, modify_both, remove, accept
        'title',
        'description',
        'location_x',
        'location_y',
        'location_z',
        'clash_distance', // المسافة أو التداخل
        'clearance_required', // المسافة المطلوبة
        'assigned_to',
        'assigned_at',
        'due_date',
        'resolved_at',
        'resolved_by',
        'resolution_notes',
        'viewpoint', // Camera position for viewer
        'screenshot_path',
        'comments',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'location_x' => 'decimal:6',
        'location_y' => 'decimal:6',
        'location_z' => 'decimal:6',
        'clash_distance' => 'decimal:4',
        'clearance_required' => 'decimal:4',
        'assigned_at' => 'datetime',
        'due_date' => 'date',
        'resolved_at' => 'datetime',
        'viewpoint' => 'array',
        'comments' => 'array',
        'metadata' => 'array',
    ];

    // Clash Types
    const TYPE_HARD = 'hard'; // تداخل فيزيائي
    const TYPE_SOFT = 'soft'; // قريب جداً
    const TYPE_CLEARANCE = 'clearance'; // انتهاك مسافة الخلوص
    const TYPE_DUPLICATE = 'duplicate'; // عناصر متكررة
    const TYPE_WORKFLOW = 'workflow'; // تعارض في تسلسل العمل

    // Severity Levels
    const SEVERITY_CRITICAL = 'critical';
    const SEVERITY_MAJOR = 'major';
    const SEVERITY_MINOR = 'minor';
    const SEVERITY_INFO = 'informational';

    // Status
    const STATUS_NEW = 'new';
    const STATUS_ACTIVE = 'active';
    const STATUS_APPROVED = 'approved';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_IGNORED = 'ignored';

    // Resolution Types
    const RESOLUTION_MODIFY_E1 = 'modify_element1';
    const RESOLUTION_MODIFY_E2 = 'modify_element2';
    const RESOLUTION_MODIFY_BOTH = 'modify_both';
    const RESOLUTION_REMOVE = 'remove';
    const RESOLUTION_ACCEPT = 'accept';

    // ===== العلاقات =====

    public function bimProject()
    {
        return $this->belongsTo(BimProject::class);
    }

    public function element1()
    {
        return $this->belongsTo(BimElement::class, 'element1_id');
    }

    public function element2()
    {
        return $this->belongsTo(BimElement::class, 'element2_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ===== Scopes =====

    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_NEW, self::STATUS_ACTIVE]);
    }

    public function scopeResolved($query)
    {
        return $query->where('status', self::STATUS_RESOLVED);
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', self::SEVERITY_CRITICAL);
    }

    public function scopeMajor($query)
    {
        return $query->where('severity', self::SEVERITY_MAJOR);
    }

    public function scopeHard($query)
    {
        return $query->where('clash_type', self::TYPE_HARD);
    }

    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_to');
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
                     ->whereIn('status', [self::STATUS_NEW, self::STATUS_ACTIVE]);
    }

    public function scopeByDiscipline($query, string $discipline)
    {
        return $query->whereHas('element1', function ($q) use ($discipline) {
            $q->where('discipline', $discipline);
        })->orWhereHas('element2', function ($q) use ($discipline) {
            $q->where('discipline', $discipline);
        });
    }

    // ===== Accessors =====

    public function getClashTypeNameAttribute()
    {
        $types = [
            'hard' => 'تصادم صلب',
            'soft' => 'تصادم ناعم',
            'clearance' => 'انتهاك خلوص',
            'duplicate' => 'تكرار',
            'workflow' => 'تعارض تسلسل',
        ];
        return $types[$this->clash_type] ?? $this->clash_type;
    }

    public function getSeverityNameAttribute()
    {
        $severities = [
            'critical' => 'حرج',
            'major' => 'رئيسي',
            'minor' => 'ثانوي',
            'informational' => 'معلوماتي',
        ];
        return $severities[$this->severity] ?? $this->severity;
    }

    public function getStatusNameAttribute()
    {
        $statuses = [
            'new' => 'جديد',
            'active' => 'نشط',
            'approved' => 'معتمد',
            'resolved' => 'تم الحل',
            'ignored' => 'متجاهل',
        ];
        return $statuses[$this->status] ?? $this->status;
    }

    public function getSeverityColorAttribute()
    {
        $colors = [
            'critical' => '#DC2626', // red
            'major' => '#F59E0B', // amber
            'minor' => '#3B82F6', // blue
            'informational' => '#6B7280', // gray
        ];
        return $colors[$this->severity] ?? '#6B7280';
    }

    public function getIsOverdueAttribute()
    {
        return $this->due_date && 
               $this->due_date < now() && 
               in_array($this->status, [self::STATUS_NEW, self::STATUS_ACTIVE]);
    }

    public function getDaysToResolutionAttribute()
    {
        if (!$this->due_date) {
            return null;
        }

        if ($this->status === self::STATUS_RESOLVED) {
            return $this->created_at->diffInDays($this->resolved_at);
        }

        return now()->diffInDays($this->due_date, false);
    }

    public function getLocationAttribute()
    {
        return [
            'x' => $this->location_x,
            'y' => $this->location_y,
            'z' => $this->location_z,
        ];
    }

    public function getElementsInfoAttribute()
    {
        return [
            'element1' => [
                'id' => $this->element1?->element_id,
                'type' => $this->element1?->element_type,
                'discipline' => $this->element1?->discipline,
            ],
            'element2' => [
                'id' => $this->element2?->element_id,
                'type' => $this->element2?->element_type,
                'discipline' => $this->element2?->discipline,
            ],
        ];
    }

    // ===== Methods =====

    /**
     * إسناد التصادم لمستخدم
     */
    public function assignTo($userId, ?string $dueDate = null): self
    {
        $this->update([
            'assigned_to' => $userId,
            'assigned_at' => now(),
            'due_date' => $dueDate ?? now()->addDays(7),
            'status' => self::STATUS_ACTIVE,
        ]);

        $this->addComment("تم الإسناد إلى " . $this->assignedTo->name);
        
        return $this;
    }

    /**
     * حل التصادم
     */
    public function resolve(string $resolutionType, string $notes = null, $resolvedBy = null): self
    {
        $this->update([
            'status' => self::STATUS_RESOLVED,
            'resolution_type' => $resolutionType,
            'resolution_notes' => $notes,
            'resolved_at' => now(),
            'resolved_by' => $resolvedBy ?? auth()->id(),
        ]);

        $this->addComment("تم حل التصادم: " . $this->getResolutionTypeName($resolutionType));
        
        return $this;
    }

    /**
     * تجاهل التصادم
     */
    public function ignore(string $reason = null): self
    {
        $this->update([
            'status' => self::STATUS_IGNORED,
            'resolution_notes' => $reason,
        ]);

        $this->addComment("تم تجاهل التصادم: " . ($reason ?? 'بدون سبب'));
        
        return $this;
    }

    /**
     * إعادة فتح التصادم
     */
    public function reopen(string $reason = null): self
    {
        $this->update([
            'status' => self::STATUS_ACTIVE,
            'resolved_at' => null,
            'resolved_by' => null,
            'resolution_type' => null,
        ]);

        $this->addComment("تم إعادة فتح التصادم: " . ($reason ?? 'بدون سبب'));
        
        return $this;
    }

    /**
     * إضافة تعليق
     */
    public function addComment(string $text, $userId = null): self
    {
        $comments = $this->comments ?? [];
        $comments[] = [
            'user_id' => $userId ?? auth()->id(),
            'text' => $text,
            'created_at' => now()->toISOString(),
        ];

        $this->update(['comments' => $comments]);
        
        return $this;
    }

    /**
     * حفظ نقطة النظر للعارض
     */
    public function saveViewpoint(array $cameraPosition, array $target = null): self
    {
        $this->update([
            'viewpoint' => [
                'camera' => $cameraPosition,
                'target' => $target ?? $this->location,
                'saved_at' => now()->toISOString(),
            ],
        ]);
        
        return $this;
    }

    /**
     * الحصول على اسم نوع الحل
     */
    protected function getResolutionTypeName(string $type): string
    {
        $types = [
            'modify_element1' => 'تعديل العنصر الأول',
            'modify_element2' => 'تعديل العنصر الثاني',
            'modify_both' => 'تعديل كلا العنصرين',
            'remove' => 'إزالة أحد العناصر',
            'accept' => 'قبول التصادم',
        ];
        return $types[$type] ?? $type;
    }

    /**
     * تصدير للعارض
     */
    public function toViewerJson(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->clash_type,
            'severity' => $this->severity,
            'status' => $this->status,
            'location' => $this->location,
            'element1Id' => $this->element1?->element_id,
            'element2Id' => $this->element2?->element_id,
            'viewpoint' => $this->viewpoint,
            'description' => $this->description,
            'color' => $this->severity_color,
        ];
    }

    /**
     * إنشاء تقرير التصادمات
     */
    public static function generateReport($bimProjectId): array
    {
        $clashes = static::where('bim_project_id', $bimProjectId)->get();
        
        return [
            'total' => $clashes->count(),
            'by_status' => $clashes->groupBy('status')->map->count(),
            'by_severity' => $clashes->groupBy('severity')->map->count(),
            'by_type' => $clashes->groupBy('clash_type')->map->count(),
            'critical_unresolved' => $clashes->where('severity', 'critical')
                ->whereIn('status', ['new', 'active'])->count(),
            'overdue' => $clashes->filter->is_overdue->count(),
            'avg_resolution_days' => $clashes->where('status', 'resolved')
                ->avg(fn($c) => $c->created_at->diffInDays($c->resolved_at)),
            'by_discipline' => $clashes->groupBy(function ($clash) {
                return $clash->element1?->discipline . ' vs ' . $clash->element2?->discipline;
            })->map->count(),
        ];
    }
}
