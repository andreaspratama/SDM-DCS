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
        'has_official_activity',
        'official_activity_name',
    ];

    protected $casts = [
        'date' => 'date',
        'is_workday' => 'boolean',
        'has_official_activity' => 'boolean',
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}