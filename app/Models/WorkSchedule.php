<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkSchedule extends Model
{
    protected $guarded = [];


    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }


    public function days(): HasMany
    {
        return $this->hasMany(
            WorkScheduleDay::class
        );
    }
}