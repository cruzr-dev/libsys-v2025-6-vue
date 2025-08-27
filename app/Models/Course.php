<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    protected $fillable = [];

    public function college(): BelongsTo
    {
        return $this->belongsTo(College::class);
    }

    public function majors(): HasMany
    {
        return $this->hasMany(Major::class);
    }
}
