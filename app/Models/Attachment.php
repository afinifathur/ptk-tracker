<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Attachment extends Model implements AuditableContract
{
    use Auditable;

    /**
     * Kolom yang boleh di-mass-assign.
     */
    protected $fillable = [
        'ptk_id',
        'original_name',
        'caption',
        'path',
        'mime',
        'size',
        'uploaded_by',
    ];

    /**
     * Kolom yang dicatat dalam audit.
     */
    protected array $auditInclude = [
        'ptk_id',
        'original_name',
        'caption',
        'path',
        'mime',
        'size',
        'uploaded_by',
    ];

    /**
     * Casting atribut.
     */
    protected $casts = [
        'size' => 'integer',
        'ptk_id' => 'integer',
        'uploaded_by' => 'integer',
    ];

    /**
     * Relasi ke PTK.
     */
    public function ptk(): BelongsTo
    {
        // FK default: ptk_id
        return $this->belongsTo(PTK::class);
    }

    /**
     * Relasi ke user pengunggah.
     */
    public function uploader(): BelongsTo
    {
        // FK: uploaded_by -> users.id
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
