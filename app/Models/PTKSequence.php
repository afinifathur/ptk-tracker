<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PTKSequence extends Model
{
    protected $fillable = ['department_id','year','month','last_run'];
}
