<?php

namespace App\Models;

use App\Models\{Category, Subcategory, Department, User, Attachment};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
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
