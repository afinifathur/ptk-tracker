<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PtkMtcDetail extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'installation_date' => 'date',
    ];

    public function ptk()
    {
        return $this->belongsTo(PTK::class);
    }

    public function spareparts()
    {
        return $this->hasMany(PtkSparepart::class, 'ptk_mtc_detail_id');
    }
}
