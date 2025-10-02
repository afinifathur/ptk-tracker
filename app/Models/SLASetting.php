<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class SLASetting extends Model
{
  protected $fillable = ['entity_type','entity_id','days'];
}