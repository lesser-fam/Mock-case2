<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;


class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'user_id');
    }

    public function attendanceCorrectionRequests()
    {
        return $this->hasMany(AttendanceCorrectionRequest::class, 'user_id');
    }

    public function approvedAttendanceCorrectionRequests()
    {
        return $this->hasMany(AttendanceCorrectionRequest::class, 'approved_by');
    }

    public function getSeiAttribute(): string
    {
        $name = trim((string)($this->name ?? ''));
        if ($name === '') return '';

        $parts = preg_split('/\s+/u', $name);
        return $parts[0] ?? $name;
    }

    public function getMeiAttribute(): string
    {
        $name = trim((string)($this->name ?? ''));
        if ($name === '') return '';

        $parts = preg_split('/\s+/u', $name);
        return $parts[1] ?? '';
    }
}
