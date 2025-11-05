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
    ];

    protected $casts = [
        'due_date'    => 'datetime',
        'form_date'   => 'datetime',
        'approved_at' => 'datetime',
    ];

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
    ];

    // =====================================================================
    // CONSTANTS
    // =====================================================================
    public const STATUS_NOT_STARTED = 'Not Started';
    public const STATUS_IN_PROGRESS = 'In Progress';
    public const STATUS_DONE        = 'Done';

    // =====================================================================
    // MUTATORS
    // =====================================================================
    public function setNumberAttribute($value): void
    {
        if (!empty($this->attributes['number'])) {
            return;
        }
        $this->attributes['number'] = $value;
    }

    // =====================================================================
    // SCOPES
    // =====================================================================

    /**
     * Scope utama: filter visibilitas berdasarkan role pembuat (PIC role).
     *
     * Aturan:
     * - director & auditor: lihat semua
     * - kabag_qc: lihat semua PTK yg dibuat oleh admin_qc_flange / admin_qc_fitting
     * - manager_hr: lihat semua PTK yg dibuat oleh admin_hr / admin_k3
     * - admin (lainnya): hanya lihat PTK yg dibuat dirinya sendiri
     */
    public function scopeVisibleTo(Builder $q, User $user): Builder
    {
        // Director & Auditor → semua PTK
        if ($user->hasAnyRole(['director', 'auditor'])) {
            return $q;
        }

        // Kabag QC → PTK dari admin QC Flange/Fitting
        if ($user->hasRole('kabag_qc')) {
            return $q->whereHas('pic.roles', function ($r) {
                $r->whereIn('name', ['admin_qc_flange', 'admin_qc_fitting']);
            });
        }

        // Manager HR → PTK dari admin HR / K3
        if ($user->hasRole('manager_hr')) {
            return $q->whereHas('pic.roles', function ($r) {
                $r->whereIn('name', ['admin_hr', 'admin_k3']);
            });
        }

        // Admin (lainnya) → hanya yang dia buat sendiri
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
     * Scope pencarian ringan (title, number, desc).
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
     * Scope untuk filter PTK milik user tertentu (PIC atau creator).
     */
    public function scopeOwnedBy(Builder $q, User $user): Builder
    {
        return $q->where(function (Builder $qq) use ($user) {
            $qq->where('pic_user_id', $user->id)
                ->orWhere('created_by', $user->id);
        });
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

    /**
     * User yang menjadi PIC (pembuat utama PTK)
     */
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
}
