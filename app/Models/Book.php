<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Book extends Model
{
    protected $guarded = [];

    protected $dates = [
        'ics_date',
        'pr_date',
        'po_date',
    ];

    protected $casts = [
        'purchase_amount' => 'decimal:2',
        'lot_cost' => 'decimal:2',
    ];

    public function record(): BelongsTo
    {
        return $this->belongsTo(Record::class);
    }

    public function ddcClassification(): BelongsTo
    {
        return $this->belongsTo(DdcClassification::class, 'ddc_class_id');
    }

    public function lcClassification(): BelongsTo
    {
        return $this->belongsTo(LcClassification::class, 'lc_class_id');
    }

    public function physicalLocation(): BelongsTo
    {
        return $this->belongsTo(PhysicalLocation::class);
    }

    public function coverType(): BelongsTo
    {
        return $this->belongsTo(CoverType::class, 'cover_type_id');
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    public function authors(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Author::class, 'author_book')->withPivot('role')->withTimestamps();
    }

    public function editors(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Author::class, 'book_editor')->withTimestamps();
    }
}
