<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeWorkSchedule extends Model
{
    protected $fillable = [
        'employee_id',
        'tanggal_mulai',
        'tanggal_selesai',
        'nama',
        'keterangan',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];


    public function employee(): BelongsTo
    {
        return $this->belongsTo(
            Employee::class
        );
    }


    public function days(): HasMany
    {
        return $this->hasMany(
            EmployeeWorkScheduleDay::class
        );
    }
}