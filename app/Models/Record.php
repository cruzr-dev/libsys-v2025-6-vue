<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Record extends Model
{
    use hasFactory;

    protected $table = 'records';

    protected $guarded = [];

    protected $casts = [
        'subject_headings' => 'array',
    ];

    public function book(): HasOne
    {
        return $this->hasOne(Book::class);
    }

    public function digitalResource(): HasOne
    {
        return $this->hasOne(DigitalResource::class);
    }

    public function periodical(): HasOne
    {
        return $this->hasOne(Periodical::class);
    }

    public function thesis(): HasOne
    {
        return $this->hasOne(Thesis::class);
    }

    public function remarks(): Record|\Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Remark::class);
    }
}
