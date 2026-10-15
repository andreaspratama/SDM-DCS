<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeAnnualLeave extends Model
{
    protected $fillable = [
        'employee_id',
        'leave_period_id',
        'allowance_days',
        'is_eligible',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'allowance_days' => 'integer',
            'is_eligible' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(
            LeavePeriod::class,
            'leave_period_id'
        );
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(
            AnnualLeaveTransaction::class,
            'employee_annual_leave_id'
        );
    }
}
