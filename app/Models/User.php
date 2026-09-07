<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\AttendancePermission;

#[Fillable(['name', 'email', 'password', 'role', 'unit_id', 'employee_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'Admin';
    }

    public function isPimpinan(): bool
    {
        return $this->role === 'Pimpinan';
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function permissionsToApprove()
    {
        return $this->hasMany(
            AttendancePermission::class,
            'approver_user_id'
        );
    }
}
