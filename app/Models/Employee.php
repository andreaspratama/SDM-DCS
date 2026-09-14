<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active'     => 'boolean',
            'tanggal_masuk' => 'date',
            'tanggal_keluar'=> 'date',
        ];
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function permissions()
    {
        return $this->hasMany(AttendancePermission::class);
    }

    public function workSchedule()
    {
        return $this->belongsTo(WorkSchedule::class);
    }

    public function employeeWorkSchedules(): HasMany
    {
        return $this->hasMany(EmployeeWorkSchedule::class);
    }

    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function user()
    {
        return $this->hasOne(User::class);
    }

    public function unitHistories(): HasMany
    {
        return $this->hasMany(EmployeeUnitHistory::class);
    }
}
