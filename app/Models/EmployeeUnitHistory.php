<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeUnitHistory extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'tanggal_mulai'   => 'date',
            'tanggal_selesai' => 'date',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function workSchedule()
    {
        return $this->belongsTo(WorkSchedule::class);
    }
}