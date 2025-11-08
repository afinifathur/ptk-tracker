<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class PTK extends Model implements AuditableContract
{
    use SoftDeletes, Auditable;

    protected $table = 'ptks';

    // =====================================================================
    // MASS ASSIGNMENT
    // =====================================================================
    protected $fillable = [
        'number',
        'title',
        'description',
        'desc_nc',
        'evaluation',
        'action_correction',
        'action_corrective',
        'category_id',
        'subcategory_id',
        'department_id',
        'pic_user_id',
        'status',
        'due_date',
        'form_date',
        'approved_at',   // legacy (jika masih dipakai)
        'approver_id',   // legacy
        'director_id',   // legacy
        'created_by',

        // --- STAGES & REJECT INFO ---
        'approved_stage1_by',
        'approved_stage1_at',
        'approved_stage2_by',
        'approved_stage2_at',
        'last_reject_stage',
        'last_reject_reason',
        'last_reject_by',
        'last_reject_at',
    ];

    // =====================================================================
    // CASTS
    // =====================================================================
    protected $casts = [
        'due_date'           => 'datetime',
        'form_date'          => 'datetime',
        'approved_at'        => 'datetime',
        'approved_stage1_at' => 'datetime',
        'approved_stage2_at' => 'datetime',
        'last_reject_at'     => 'datetime',
    ];

    // =====================================================================
    // AUDIT
    // =====================================================================
    protected array $auditInclude = [
        'number',
        'title',
        'description',
        'desc_nc',
        'evaluation',
        'action_correction',
        'action_corrective',
        'category_id',
        'subcategory_id',
        'department_id',
        'pic_user_id',
        'status',
        'due_date',
        'form_date',
        'approved_at',
        'approver_id',
        'director_id',
        'created_by',

        // --- STAGES & REJECT INFO ---
        'approved_stage1_by',
        'approved_stage1_at',
        'approved_stage2_by',
        'approved_stage2_at',
        'last_reject_stage',
        'last_reject_reason',
        'last_reject_by',
        'last_reject_at',
    ];

    // =====================================================================
    // STATUS CONSTANTS
    // =====================================================================
    public const STATUS_NOT_STARTED       = 'Not Started';
    public const STATUS_IN_PROGRESS       = 'In Progress';
    public const STATUS_SUBMITTED         = 'Submitted';
    public const STATUS_WAITING_DIRECTOR  = 'Waiting Director';
    public const STATUS_COMPLETED         = 'Completed';

    // =====================================================================
    // MUTATORS
    // =====================================================================
    /**
     * Nomor PTK boleh diubah hingga sebelum masuk alur approval (Submitted+).
     */
    public function setNumberAttribute($value): void
    {
        $locked = [
            self::STATUS_SUBMITTED,
            self::STATUS_WAITING_DIRECTOR,
            self::STATUS_COMPLETED,
        ];

        // Kunci perubahan nomor jika status original sudah masuk approval/completed
        if (in_array($this->getOriginal('status'), $locked, true)) {
            return;
        }

        $this->attributes['number'] = $value;
    }

    // =====================================================================
    // SCOPES
    // =====================================================================

    /**
     * Scope visibilitas berbasis ROLE PIC (bukan departemen).
     *
     * - director & auditor: lihat semua
     * - kabag_qc: PTK dari PIC ber-role admin_qc_flange/fitting
     * - manager_hr: PTK dari PIC ber-role admin_hr/admin_k3
     * - lainnya: hanya PTK dengan pic_user_id = user
     */
    public function scopeVisibleTo(Builder $q, User $user): Builder
    {
        if ($user->hasAnyRole(['director', 'auditor'])) {
            return $q;
        }

        if ($user->hasRole('kabag_qc')) {
            return $q->whereHas('pic.roles', fn($r) =>
                $r->whereIn('name', ['admin_qc_flange', 'admin_qc_fitting'])
            );
        }

        if ($user->hasRole('manager_hr')) {
            return $q->whereHas('pic.roles', fn($r) =>
                $r->whereIn('name', ['admin_hr', 'admin_k3'])
            );
        }

        return $q->where('pic_user_id', $user->id);
    }

    /**
     * Scope filter status.
     */
    public function scopeStatus(Builder $q, ?string $status): Builder
    {
        return $status ? $q->where('status', $status) : $q;
    }

    /**
     * Scope pencarian ringan (title, number, desc, desc_nc).
     */
    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (!$term) return $q;

        $like = '%' . str_replace('%', '\%', $term) . '%';
        return $q->where(function (Builder $qq) use ($like) {
            $qq->where('title', 'like', $like)
               ->orWhere('number', 'like', $like)
               ->orWhere('description', 'like', $like)
               ->orWhere('desc_nc', 'like', $like);
        });
    }

    /**
     * Scope untuk PTK milik user tertentu (PIC atau creator).
     */
    public function scopeOwnedBy(Builder $q, User $user): Builder
    {
        return $q->where(function (Builder $qq) use ($user) {
            $qq->where('pic_user_id', $user->id)
               ->orWhere('created_by', $user->id);
        });
    }

    // =====================================================================
    // HELPERS - STAGES
    // =====================================================================
    public function awaitingStage1(): bool
    {
        return $this->status === self::STATUS_SUBMITTED
            && is_null($this->approved_stage1_at);
    }

    public function awaitingStage2(): bool
    {
        return $this->status === self::STATUS_WAITING_DIRECTOR
            && !is_null($this->approved_stage1_at)
            && is_null($this->approved_stage2_at);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED
            && !is_null($this->approved_stage2_at);
    }

    // =====================================================================
    // RELATIONSHIPS
    // =====================================================================

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** User yang menjadi PIC */
    public function pic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_user_id');
    }

    /** (legacy) satu kolom approver_id */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /** (legacy) satu kolom director_id */
    public function director(): BelongsTo
    {
        return $this->belongsTo(User::class, 'director_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'ptk_id');
    }

    /** Penandatangan Stage 1 (Kabag/Manager) */
    public function approverStage1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_stage1_by');
    }

    /** Penandatangan Stage 2 (Direktur) */
    public function approverStage2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_stage2_by');
    }
}
