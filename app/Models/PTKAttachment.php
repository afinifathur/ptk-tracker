<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PTKAttachment extends Model
{
    // jika tabel di DB bernama 'ptk_attachments', pakai ini
    protected $table = 'attachments';

    protected $fillable = ['ptk_id','path','original_name','mime'];

    public function ptk()
    {
        return $this->belongsTo(PTK::class);
    }
}
