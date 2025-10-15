<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PTKSequence extends Model
{
    protected $table = 'ptk_sequences'; // <-- penting
    protected $fillable = ['department_id','year','month','last_run'];
}
