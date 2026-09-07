<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeWorkScheduleDay extends Model
{
    protected $fillable = [
        'employee_work_schedule_id',
        'hari',
        'jam_masuk',
        'jam_pulang',
        'is_libur',
    ];

    protected $casts = [
        'is_libur' => 'boolean',
    ];

    public function employeeWorkSchedule(): BelongsTo
    {
        return $this->belongsTo(EmployeeWorkSchedule::class);
    }
}
