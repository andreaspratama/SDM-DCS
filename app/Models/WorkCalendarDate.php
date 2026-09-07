<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WorkCalendarDate extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_year',
        'date',
        'unit_id',
        'is_workday',
        'type',
        'name',
        'description',
    ];

    protected $casts = [
        'date' => 'date',
        'is_workday' => 'boolean',
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}