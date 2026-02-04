<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceCorrectionRequest extends Model
{
    use HasFactory;

    protected $table = 'attendance_correction_requests';

    protected $fillable = [
        'attendance_id',
        'user_id',        // 申請者
        'approved_by',    // 承認者
        'date',
        'work_start_at',
        'work_end_at',
        'memo',
        'status',         // pending / approved
    ];

    protected $casts = [
        'date' => 'date',
        'work_start_at' => 'datetime',
        'work_end_at' => 'datetime',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class, 'attendance_id');
    }

    public function applicant()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function breaks()
    {
        return $this->hasMany(AttendanceCorrectionRequestBreak::class, 'request_id');
    }
}
