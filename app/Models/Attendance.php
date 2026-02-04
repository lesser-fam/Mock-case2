<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'work_start_at',
        'work_end_at',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
        'work_start_at' => 'datetime',
        'work_end_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function breaks()
    {
        return $this->hasMany(BreakTime::class, 'attendance_id');
    }

    public function correctionRequests()
    {
        return $this->hasMany(AttendanceCorrectionRequest::class, 'attendance_id');
    }
}
