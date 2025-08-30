<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Editor extends Model
{
    protected $guarded = [];
    public function books()
    {
        return $this->belongsToMany(Book::class, 'book_editor')
            ->withPivot('role')
            ->withTimestamps();
    }
}
