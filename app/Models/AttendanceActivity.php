<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceActivity extends Model
{
    protected $guarded = [];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}
