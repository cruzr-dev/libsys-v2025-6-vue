<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class College extends Model
{
    protected $fillable = [];

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }
}
