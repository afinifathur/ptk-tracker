<?php
declare(strict_types=1);

namespace App\Models;

use App\Models\{Attachment, Category, Department, Subcategory, User};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class PTK extends Model implements AuditableContract
{
    use SoftDeletes, Auditable;

    /**
     * Nama tabel (sesuai migration).
     */
    protected $table = 'ptks';

    /**
     * Kolom yang boleh diisi mass assignment.
     * (pastikan department_id & pic_user_id ter-cover)
     */
    protected $fillable = [
        'number',
        'title',
        'description',
        'desc_nc',              // renamed from description_nc
        'evaluation',
        'action_correction',    // renamed from correction_action
        'action_corrective',    // renamed from corrective_action
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

    /**
     * Casting atribut tanggal.
     */
    protected $casts = [
        'due_date'    => 'datetime',
        'form_date'   => 'datetime',
        'approved_at' => 'datetime',
    ];

    /**
     * Kolom yang diikutkan dalam audit.
     */
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
    // CONSTANTS (opsional, membantu konsistensi)
    // =====================================================================
    public const STATUS_NOT_STARTED = 'Not Started';
    public const STATUS_IN_PROGRESS = 'In Progress';
    public const STATUS_DONE        = 'Done';

    // =====================================================================
    // MUTATOR — number immutable (kalau sudah ada, jangan ditimpa)
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
     * Scope visibilitas generik berdasarkan role TANPA mengunci departemen saat create.
     *
     * Aturan:
     * - director / auditor  => lihat semua
     * - role admin (admin_qc_flange, admin_qc_fitting, admin_hr, admin_k3, kabag_qc, manager_hr)
     *     => lihat (PTK yang dia PIC/creator) ATAU (PTK di departemen user)
     * - lainnya => hanya (PIC/creator)
     */
    public function scopeVisibleTo(Builder $q, User $user): Builder
    {
        // 1) Full access
        if ($user->hasAnyRole(['director', 'auditor', 'superadmin'])) {
            return $q;
        }

        // 2) Admin/manager level (akses gabungan: PIC/creator OR department user)
        if ($user->hasAnyRole([
            'kabag_qc',
            'manager_hr',
            'admin_qc_flange',
            'admin_qc_fitting',
            'admin_hr',
            'admin_k3',
        ])) {
            return $q->where(function (Builder $qq) use ($user) {
                $qq->where('pic_user_id', $user->id)
                   ->orWhere('created_by', $user->id)
                   ->orWhere('department_id', $user->department_id);
            });
        }

        // 3) Default (non-admin): PIC/creator saja
        return $q->where(function (Builder $qq) use ($user) {
            $qq->where('pic_user_id', $user->id)
               ->orWhere('created_by', $user->id);
        });
    }

    /**
     * Scope helper untuk status.
     */
    public function scopeStatus(Builder $q, ?string $status): Builder
    {
        return $status ? $q->where('status', $status) : $q;
    }

    /**
     * Scope pencarian ringan di judul/nomor/deskripsi.
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
     * Scope untuk filter milik user (PIC/creator).
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
