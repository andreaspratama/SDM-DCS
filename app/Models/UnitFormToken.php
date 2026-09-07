<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitFormToken extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'token' => 'encrypted',
    ];

    /**
     * Token ini milik unit tertentu.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
