<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AttendancePermission extends Model
{
    use HasFactory;

    // =====================================================
    // STATUS APPROVAL
    // =====================================================
    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'employee_id',

        'date_start',
        'date_end',

        'time_start',
        'time_end',

        'type',
        'description',
        'attachment',

        // Approval
        'status',
        'approver_user_id',
        'approved_by',
        'approved_at',
        'approval_note',
    ];


    protected $casts = [
        'date_start'   => 'date',
        'date_end'     => 'date',
        'approved_at'  => 'datetime',
    ];


    // =====================================================
    // EMPLOYEE
    // =====================================================
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }


    // =====================================================
    // USER / PIMPINAN YANG APPROVE
    // =====================================================
    public function approvedBy()
    {
        return $this->belongsTo(
            User::class,
            'approved_by'
        );
    }


    // =====================================================
    // HELPER STATUS
    // =====================================================
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function approver()
    {
        return $this->belongsTo(
            User::class,
            'approver_user_id'
        );
    }
}
