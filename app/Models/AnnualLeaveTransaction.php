<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnnualLeaveTransaction extends Model
{
    public const TYPE_DEBIT = 'debit';
    public const TYPE_CREDIT = 'credit';

    protected $fillable = [
        'employee_annual_leave_id',
        'attendance_permission_id',
        'type',
        'days',
        'description',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'days' => 'integer',
        ];
    }

    public function employeeAnnualLeave(): BelongsTo
    {
        return $this->belongsTo(
            EmployeeAnnualLeave::class,
            'employee_annual_leave_id'
        );
    }

    public function permission(): BelongsTo
    {
        return $this->belongsTo(
            AttendancePermission::class,
            'attendance_permission_id'
        );
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
