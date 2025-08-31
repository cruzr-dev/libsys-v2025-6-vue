<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DigitalResource extends Model
{
    use HasFactory;

    public $guarded = [];

    protected $casts = [
        'authors' => 'array', // saved as json in db
        'editors' => 'array', // saved as json in db
        'purchase_amount' => 'decimal:2',
        'lot_cost' => 'decimal:2',
    ];
}
