<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    /**
     * Kolom yang boleh diisi mass-assignment.
     *
     * @var array<int, string>
     */
    protected $fillable = ['name'];

    /**
     * Relasi: Category memiliki banyak PTK.
     */
    public function ptk(): HasMany
    {
        return $this->hasMany(PTK::class);
    }

    /**
     * Relasi: Category memiliki banyak Subcategory.
     */
    public function subcategories(): HasMany
    {
        return $this->hasMany(Subcategory::class);
    }
}
