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
     * Nama tabel (sesuaikan dengan migration).
     */
    protected $table = 'ptks';

    /**
     * Kolom yang boleh di-mass assign.
     *
     * Pastikan migration memiliki kolom: subcategory_id.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'number',
        'title',
        'description',
        'category_id',
        'subcategory_id',
        'department_id',
        'pic_user_id',
        'status',
        'due_date',
        'approved_at',
        'approver_id',
        'director_id',
    ];

    /**
     * Cast atribut.
     */
    protected $casts = [
        'due_date'    => 'date',
        'approved_at' => 'datetime',
    ];

    /**
     * Kolom yang diikutkan dalam audit.
     *
     * @var array<int, string>
     */
    protected array $auditInclude = [
        'number',
        'title',
        'description',
        'category_id',
        'subcategory_id',
        'department_id',
        'pic_user_id',
        'status',
        'due_date',
        'approved_at',
        'approver_id',
        'director_id',
    ];

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
     * Pastikan foreign key di tabel attachments = 'ptk_id'.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'ptk_id');
    }
}
