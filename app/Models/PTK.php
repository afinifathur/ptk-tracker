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

    /**
     * Nama tabel (sesuai migration).
     */
    protected $table = 'ptks';

    /**
     * Kolom yang boleh diisi mass assignment.
     */
    protected $fillable = [
        'number',
        'title',
        'description',
        'description_nc',
        'evaluation',
        'correction_action',
        'corrective_action',
        'category_id',
        'subcategory_id',
        'department_id',
        'pic_user_id',
        'status',
        'due_date',
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
        'approved_at' => 'datetime',
    ];

    /**
     * Kolom yang diikutkan dalam audit.
     */
    protected array $auditInclude = [
        'number',
        'title',
        'description',
        'description_nc',
        'evaluation',
        'correction_action',
        'corrective_action',
        'category_id',
        'subcategory_id',
        'department_id',
        'pic_user_id',
        'status',
        'due_date',
        'approved_at',
        'approver_id',
        'director_id',
        'created_by',
    ];

    # =========================================================
    # MUTATOR — Lindungi nomor agar immutable
    # =========================================================
    public function setNumberAttribute($value): void
    {
        if (!empty($this->attributes['number'])) {
            return; // sudah ada, jangan ditimpa
        }

        $this->attributes['number'] = $value;
    }

    # =========================================================
    # SCOPE — Batasi visibilitas PTK otomatis berdasarkan role
    # =========================================================
    public function scopeVisibleTo(Builder $q, User $user): Builder
    {
        $deptId = fn($n) => Department::where('name', $n)->value('id');

        if ($user->hasRole('director|auditor')) {
            return $q;
        }

        if ($user->hasRole('kabag_qc')) {
            return $q->whereIn('department_id', array_filter([
                $deptId('Flange'),
                $deptId('Fitting'),
            ]));
        }

        if ($user->hasRole('admin_qc_flange')) {
            return $q->where('department_id', $deptId('Flange'));
        }

        if ($user->hasRole('admin_qc_fitting')) {
            return $q->where('department_id', $deptId('Fitting'));
        }

        if ($user->hasRole('manager_hr')) {
            return $q->whereIn('department_id', array_filter([
                $deptId('HR'),
                $deptId('K3 & Lingkungan'),
            ]));
        }

        if ($user->hasRole('admin_hr')) {
            return $q->where('department_id', $deptId('HR'));
        }

        if ($user->hasRole('admin_k3')) {
            return $q->where('department_id', $deptId('K3 & Lingkungan'));
        }

        // Default: hanya data sendiri (PIC atau creator)
        return $q->where(function ($qq) use ($user) {
            $qq->where('pic_user_id', $user->id)
               ->orWhere('created_by', $user->id);
        });
    }

    # =========================================================
    # RELATIONSHIPS
    # =========================================================
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
