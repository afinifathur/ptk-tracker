<?php

namespace App\Models;

use App\Models\{Category, Subcategory, Department, User, Attachment};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Illuminate\Database\Eloquent\Builder;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

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

    // ========================
    // Mutators / Guards
    // ========================

    /**
     * Lindungi nomor agar immutable (tidak bisa diubah setelah diisi).
     */
    public function setNumberAttribute($value): void
    {
        if (!empty($this->attributes['number'])) {
            return; // sudah ada, jangan ditimpa
        }

        $this->attributes['number'] = $value;
    }

    // ========================
    // Query Scopes (Visibility)
    // ========================

    /**
     * Batasi visibilitas PTK otomatis berdasarkan role user.
     *
     * Contoh:
     * PTK::visibleTo(auth()->user())->with(['department','category','pic'])->paginate(20);
     */
    public function scopeVisibleTo(Builder $q, User $user): Builder
    {
        // helper ambil id departemen by name
        $deptId = fn(string $name) => Department::where('name', $name)->value('id');

        // Full akses
        if ($user->hasRole('director|auditor')) {
            return $q;
        }

        // 🔸 Kabag QC lihat Flange + Fitting
        if ($user->hasRole('kabag_qc')) {
            $flange  = $deptId('Flange');
            $fitting = $deptId('Fitting');
            return $q->whereIn('department_id', array_filter([$flange, $fitting]));
        }

        // 🔸 Admin QC Flange
        if ($user->hasRole('admin_qc_flange')) {
            return $q->where('department_id', $deptId('Flange'));
        }

        // 🔸 Admin QC Fitting
        if ($user->hasRole('admin_qc_fitting')) {
            return $q->where('department_id', $deptId('Fitting'));
        }

        // 🔸 Manager HR → lihat HR + K3
        if ($user->hasRole('manager_hr')) {
            $hr = $deptId('HR');
            $k3 = $deptId('K3 & Lingkungan');
            return $q->whereIn('department_id', array_filter([$hr, $k3]));
        }

        // 🔸 Admin HR
        if ($user->hasRole('admin_hr')) {
            return $q->where('department_id', $deptId('HR'));
        }

        // 🔸 Admin K3
        if ($user->hasRole('admin_k3')) {
            return $q->where('department_id', $deptId('K3 & Lingkungan'));
        }

        // default: hanya data sendiri (PIC atau creator)
        return $q->where(function (Builder $qq) use ($user) {
            $qq->where('pic_user_id', $user->id)
               ->orWhere('created_by', $user->id);
        });
    }

    // ========================
    // Relationships
    // ========================

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

    /**
     * Relasi ke user pembuat (creator)
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relasi ke attachments (foreign key: ptk_id)
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'ptk_id');
    }
}
