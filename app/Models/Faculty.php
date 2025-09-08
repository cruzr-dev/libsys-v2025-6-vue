<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faculty extends Model
{
    protected $guarded = [];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function college(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(College::class);
    }

    public function course(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
