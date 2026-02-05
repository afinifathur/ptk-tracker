<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PtkSparepart extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'order_date' => 'date',
        'est_arrival_date' => 'date',
        'actual_arrival_date' => 'date',
    ];

    public function detail()
    {
        return $this->belongsTo(PtkMtcDetail::class, 'ptk_mtc_detail_id');
    }
}
