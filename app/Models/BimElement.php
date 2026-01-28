<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BimElement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'bim_project_id',
        'element_id', // GUID من IFC
        'ifc_type', // IfcWall, IfcBeam, IfcColumn, etc.
        'element_type', // Wall, Beam, Column, Slab, etc.
        'name',
        'description',
        'discipline', // Architecture, Structure, MEP, etc.
        'level',
        'zone',
        'system',
        'material',
        'boq_item_id',
        'gantt_task_id',
        'cost_code',
        // Geometry
        'center_x',
        'center_y',
        'center_z',
        'min_x',
        'min_y',
        'min_z',
        'max_x',
        'max_y',
        'max_z',
        'rotation_x',
        'rotation_y',
        'rotation_z',
        'volume',
        'area',
        'length',
        'width',
        'height',
        // Quantities (5D)
        'calculated_quantity',
        'quantity_unit',
        'unit_cost',
        'total_cost',
        // Schedule (4D)
        'scheduled_start',
        'scheduled_end',
        'actual_start',
        'actual_end',
        'progress_percent',
        'installation_status',
        // Status
        'status',
        'is_linked',
        'is_visible',
        'is_structural',
        'color',
        'transparency',
        'properties',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'scheduled_start' => 'date',
        'scheduled_end' => 'date',
        'actual_start' => 'date',
        'actual_end' => 'date',
        'center_x' => 'decimal:6',
        'center_y' => 'decimal:6',
        'center_z' => 'decimal:6',
        'min_x' => 'decimal:6',
        'min_y' => 'decimal:6',
        'min_z' => 'decimal:6',
        'max_x' => 'decimal:6',
        'max_y' => 'decimal:6',
        'max_z' => 'decimal:6',
        'rotation_x' => 'decimal:4',
        'rotation_y' => 'decimal:4',
        'rotation_z' => 'decimal:4',
        'volume' => 'decimal:4',
        'area' => 'decimal:4',
        'length' => 'decimal:4',
        'width' => 'decimal:4',
        'height' => 'decimal:4',
        'calculated_quantity' => 'decimal:4',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'progress_percent' => 'decimal:2',
        'is_linked' => 'boolean',
        'is_visible' => 'boolean',
        'is_structural' => 'boolean',
        'transparency' => 'decimal:2',
        'properties' => 'array',
        'metadata' => 'array',
    ];

    // IFC Types
    const IFC_WALL = 'IfcWall';
    const IFC_BEAM = 'IfcBeam';
    const IFC_COLUMN = 'IfcColumn';
    const IFC_SLAB = 'IfcSlab';
    const IFC_DOOR = 'IfcDoor';
    const IFC_WINDOW = 'IfcWindow';
    const IFC_ROOF = 'IfcRoof';
    const IFC_STAIR = 'IfcStair';
    const IFC_RAILING = 'IfcRailing';
    const IFC_PIPE = 'IfcPipeSegment';
    const IFC_DUCT = 'IfcDuctSegment';
    const IFC_CABLE = 'IfcCableSegment';

    // Disciplines
    const DISCIPLINE_ARCHITECTURE = 'architecture';
    const DISCIPLINE_STRUCTURE = 'structure';
    const DISCIPLINE_MEP = 'mep';
    const DISCIPLINE_ELECTRICAL = 'electrical';
    const DISCIPLINE_PLUMBING = 'plumbing';
    const DISCIPLINE_HVAC = 'hvac';
    const DISCIPLINE_FIRE = 'fire_protection';
    const DISCIPLINE_CIVIL = 'civil';

    // Installation Status
    const INSTALL_NOT_STARTED = 'not_started';
    const INSTALL_IN_PROGRESS = 'in_progress';
    const INSTALL_COMPLETED = 'completed';
    const INSTALL_VERIFIED = 'verified';
    const INSTALL_REJECTED = 'rejected';

    // ===== العلاقات =====

    public function bimProject()
    {
        return $this->belongsTo(BimProject::class);
    }

    public function boqItem()
    {
        return $this->belongsTo(BoqItem::class);
    }

    public function ganttTask()
    {
        return $this->belongsTo(GanttTask::class);
    }

    public function clashesAsElement1()
    {
        return $this->hasMany(BimClashDetection::class, 'element1_id');
    }

    public function clashesAsElement2()
    {
        return $this->hasMany(BimClashDetection::class, 'element2_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ===== Scopes =====

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeLinked($query)
    {
        return $query->where('is_linked', true);
    }

    public function scopeUnlinked($query)
    {
        return $query->where('is_linked', false);
    }

    public function scopeScheduled($query)
    {
        return $query->whereNotNull('gantt_task_id');
    }

    public function scopeUnscheduled($query)
    {
        return $query->whereNull('gantt_task_id');
    }

    public function scopeStructural($query)
    {
        return $query->where('is_structural', true);
    }

    public function scopeByDiscipline($query, string $discipline)
    {
        return $query->where('discipline', $discipline);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('element_type', $type);
    }

    public function scopeByLevel($query, string $level)
    {
        return $query->where('level', $level);
    }

    public function scopeInProgress($query)
    {
        return $query->where('installation_status', self::INSTALL_IN_PROGRESS);
    }

    public function scopeCompleted($query)
    {
        return $query->where('installation_status', self::INSTALL_COMPLETED);
    }

    // ===== Accessors =====

    public function getAllClashesAttribute()
    {
        return $this->clashesAsElement1->merge($this->clashesAsElement2);
    }

    public function getActiveClashCountAttribute()
    {
        return $this->clashesAsElement1()->active()->count() + 
               $this->clashesAsElement2()->active()->count();
    }

    public function getBoundingBoxVolumeAttribute()
    {
        return ($this->max_x - $this->min_x) * 
               ($this->max_y - $this->min_y) * 
               ($this->max_z - $this->min_z);
    }

    public function getScheduleStatusAttribute()
    {
        if (!$this->scheduled_start || !$this->scheduled_end) {
            return 'not_scheduled';
        }

        $now = now();
        
        if ($this->installation_status === self::INSTALL_COMPLETED) {
            return $this->actual_end <= $this->scheduled_end ? 'on_time' : 'late';
        }

        if ($now < $this->scheduled_start) {
            return 'upcoming';
        }

        if ($now > $this->scheduled_end && $this->installation_status !== self::INSTALL_COMPLETED) {
            return 'overdue';
        }

        return 'in_progress';
    }

    public function getDaysDelayedAttribute()
    {
        if (!$this->scheduled_end) {
            return 0;
        }

        $endDate = $this->actual_end ?? now();
        
        if ($endDate > $this->scheduled_end) {
            return $this->scheduled_end->diffInDays($endDate);
        }

        return 0;
    }

    public function getDisciplineNameAttribute()
    {
        $disciplines = [
            'architecture' => 'العمارة',
            'structure' => 'الإنشاءات',
            'mep' => 'الميكانيكا والكهرباء والسباكة',
            'electrical' => 'الكهرباء',
            'plumbing' => 'السباكة',
            'hvac' => 'التكييف',
            'fire_protection' => 'الحماية من الحريق',
            'civil' => 'الأعمال المدنية',
        ];
        return $disciplines[$this->discipline] ?? $this->discipline;
    }

    public function getInstallationStatusNameAttribute()
    {
        $statuses = [
            'not_started' => 'لم يبدأ',
            'in_progress' => 'قيد التنفيذ',
            'completed' => 'مكتمل',
            'verified' => 'تم التحقق',
            'rejected' => 'مرفوض',
        ];
        return $statuses[$this->installation_status] ?? $this->installation_status;
    }

    // ===== Methods =====

    /**
     * حساب الكمية من الهندسة
     */
    public function calculateQuantity(): float
    {
        switch ($this->element_type) {
            case 'Wall':
            case 'Slab':
            case 'Roof':
                // مساحة سطحية
                $this->quantity_unit = 'm²';
                $this->calculated_quantity = $this->area ?? ($this->length * $this->height);
                break;

            case 'Column':
            case 'Beam':
            case 'Foundation':
                // حجم
                $this->quantity_unit = 'm³';
                $this->calculated_quantity = $this->volume ?? ($this->length * $this->width * $this->height);
                break;

            case 'Pipe':
            case 'Duct':
            case 'Cable':
                // طول
                $this->quantity_unit = 'm';
                $this->calculated_quantity = $this->length;
                break;

            case 'Door':
            case 'Window':
                // عدد
                $this->quantity_unit = 'unit';
                $this->calculated_quantity = 1;
                break;

            default:
                $this->quantity_unit = 'unit';
                $this->calculated_quantity = 1;
        }

        $this->save();
        return $this->calculated_quantity;
    }

    /**
     * حساب التكلفة
     */
    public function calculateCost(): float
    {
        if ($this->unit_cost && $this->calculated_quantity) {
            $this->total_cost = $this->unit_cost * $this->calculated_quantity;
            $this->save();
        }

        return $this->total_cost ?? 0;
    }

    /**
     * تحديث حالة التركيب
     */
    public function updateInstallationStatus(string $status, ?float $progress = null): self
    {
        $this->installation_status = $status;
        
        if ($progress !== null) {
            $this->progress_percent = $progress;
        }

        if ($status === self::INSTALL_IN_PROGRESS && !$this->actual_start) {
            $this->actual_start = now();
        }

        if ($status === self::INSTALL_COMPLETED) {
            $this->actual_end = now();
            $this->progress_percent = 100;
        }

        $this->save();
        return $this;
    }

    /**
     * ربط مع عنصر BOQ
     */
    public function linkToBoqItem($boqItemId): self
    {
        $this->update([
            'boq_item_id' => $boqItemId,
            'is_linked' => true,
        ]);

        // تحديث التكلفة من BOQ
        if ($this->boqItem) {
            $this->update([
                'unit_cost' => $this->boqItem->unit_price,
            ]);
            $this->calculateCost();
        }

        return $this;
    }

    /**
     * ربط مع مهمة جانت
     */
    public function linkToGanttTask($taskId): self
    {
        $this->update([
            'gantt_task_id' => $taskId,
        ]);

        // تحديث الجدول الزمني من المهمة
        if ($this->ganttTask) {
            $this->update([
                'scheduled_start' => $this->ganttTask->start_date,
                'scheduled_end' => $this->ganttTask->end_date,
            ]);
        }

        return $this;
    }

    /**
     * الحصول على العناصر المجاورة
     */
    public function getNeighborElements(float $distance = 1.0)
    {
        return static::where('bim_project_id', $this->bim_project_id)
            ->where('id', '!=', $this->id)
            ->whereRaw("ABS(center_x - ?) < ?", [$this->center_x, $distance])
            ->whereRaw("ABS(center_y - ?) < ?", [$this->center_y, $distance])
            ->whereRaw("ABS(center_z - ?) < ?", [$this->center_z, $distance])
            ->get();
    }

    /**
     * تصدير العنصر كـ JSON لـ Viewer
     */
    public function toViewerJson(): array
    {
        return [
            'id' => $this->element_id,
            'type' => $this->element_type,
            'name' => $this->name,
            'discipline' => $this->discipline,
            'geometry' => [
                'center' => [$this->center_x, $this->center_y, $this->center_z],
                'boundingBox' => [
                    'min' => [$this->min_x, $this->min_y, $this->min_z],
                    'max' => [$this->max_x, $this->max_y, $this->max_z],
                ],
                'rotation' => [$this->rotation_x, $this->rotation_y, $this->rotation_z],
            ],
            'appearance' => [
                'color' => $this->color ?? '#CCCCCC',
                'transparency' => $this->transparency ?? 0,
                'visible' => $this->is_visible,
            ],
            'schedule' => [
                'status' => $this->schedule_status,
                'start' => $this->scheduled_start?->format('Y-m-d'),
                'end' => $this->scheduled_end?->format('Y-m-d'),
                'progress' => $this->progress_percent,
            ],
            'cost' => [
                'quantity' => $this->calculated_quantity,
                'unit' => $this->quantity_unit,
                'unitCost' => $this->unit_cost,
                'totalCost' => $this->total_cost,
            ],
            'properties' => $this->properties,
        ];
    }
}
