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
        'approved_at',
        'approver_id',
        'director_id',
        'created_by',

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
        'due_date' => 'datetime',
        'form_date' => 'datetime',
        'approved_at' => 'datetime',
        'approved_stage1_at' => 'datetime',
        'approved_stage2_at' => 'datetime',
        'last_reject_at' => 'datetime',
    ];

    // =====================================================================
    // STATUS CONSTANTS
    // =====================================================================
    public const STATUS_NOT_STARTED = 'Not Started';
    public const STATUS_IN_PROGRESS = 'In Progress';
    public const STATUS_SUBMITTED = 'Submitted';
    public const STATUS_WAITING_DIRECTOR = 'Waiting Director';
    public const STATUS_COMPLETED = 'Completed';

    // =====================================================================
    // MUTATORS
    // =====================================================================
    public function setNumberAttribute($value): void
    {
        $locked = [
            self::STATUS_SUBMITTED,
            self::STATUS_WAITING_DIRECTOR,
            self::STATUS_COMPLETED,
        ];

        if (in_array($this->getOriginal('status'), $locked, true)) {
            return;
        }

        $this->attributes['number'] = $value;
    }

    // =====================================================================
    // SCOPES (TIDAK DIUBAH)
    // =====================================================================
    public function scopeVisibleTo(Builder $q, User $user): Builder
    {
        if ($user->hasAnyRole(['director', 'auditor'])) {
            return $q;
        }

        if ($user->hasRole('kabag_qc')) {
            return $q->whereHas(
                'pic.roles',
                fn($r) =>
                $r->whereIn('name', ['admin_qc_flange', 'admin_qc_fitting'])
            );
        }

        if ($user->hasRole('manager_hr')) {
            return $q->whereHas(
                'pic.roles',
                fn($r) =>
                $r->whereIn('name', ['admin_hr', 'admin_k3'])
            );
        }

        if ($user->hasRole('kabag_mtc')) {
            return $q->whereHas(
                'pic.roles',
                fn($r) =>
                $r->whereIn('name', ['admin_mtc'])
            );
        }

        return $q->where(function ($query) use ($user) {
            $query->where('pic_user_id', $user->id)
                ->orWhere('created_by', $user->id);
        });
    }

    // =====================================================================
    // HELPERS - STAGES (SUDAH ADA)
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
    // ✅ HELPERS - LOCK & FLOW (BARU, INTI PERBAIKAN)
    // =====================================================================

    /**
     * PTK dianggap LOCKED (read-only) ketika:
     * - sudah disubmit
     * - atau menunggu direktur
     * - atau sudah completed
     */
    public function isLocked(): bool
    {
        return in_array($this->status, [
            self::STATUS_SUBMITTED,
            self::STATUS_WAITING_DIRECTOR,
            self::STATUS_COMPLETED,
        ], true);
    }

    /**
     * PTK boleh diedit hanya jika BELUM LOCKED.
     * Dipakai oleh Policy, Controller, dan View.
     */
    public function isEditable(): bool
    {
        return !$this->isLocked();
    }

    /**
     * PTK boleh disubmit hanya dari In Progress.
     * Bukan dari Not Started, bukan dari status approval.
     */
    public function canSubmit(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    /**
     * PTK boleh dihapus hanya jika masih editable.
     */
    public function isDeletable(): bool
    {
        return $this->isEditable();
    }

    // =====================================================================
    // RELATIONSHIPS (TIDAK DIUBAH)
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

    public function pic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

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

    public function approverStage1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_stage1_by');
    }

    public function approverStage2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_stage2_by');
    }

    public function mtcDetail()
    {
        return $this->hasOne(PtkMtcDetail::class, 'ptk_id');
    }
}
