<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class Attachment extends Model implements AuditableContract
{
    use Auditable;

    /**
     * Kolom yang boleh di-mass assign.
     */
    protected $fillable = [
        'ptk_id',
        'path',
        'mime',
        'size',
        'original_name',
    ];

    /**
     * Kolom yang diikutkan dalam audit.
     */
    protected array $auditInclude = [
        'ptk_id',
        'path',
        'mime',
        'size',
        'original_name',
    ];

    /**
     * Relasi ke PTK (pastikan FK = 'ptk_id').
     */
    public function ptk(): BelongsTo
    {
        return $this->belongsTo(PTK::class, 'ptk_id');
    }
}
